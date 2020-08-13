<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\Google;

use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Date;
use Piwik\Db;
use Piwik\Metrics;
use Piwik\Site;
use Piwik\Tracker\GoalManager;
use Psr\Log\LoggerInterface;

class GoogleAnalyticsQueryService
{
    const DEFAULT_MAX_ATTEMPTS = 30;
    const MAX_BACKOFF_TIME = 60;
    const PING_MYSQL_EVERY = 25;
    const DEFAULT_MIN_BACKOFF_TIME = 2; // start at 2s since GA seems to have trouble w/ the 10 requests per 100s limit w/ 1

    private static $problematicMetrics = [
        'ga:users',
        'ga:hits',
    ];

    /**
     * @var int
     */
    private $maxAttempts = self::DEFAULT_MAX_ATTEMPTS;

    /**
     * @var \Google_Service_Analytics
     */
    private $gaService;

    /**
     * @var string
     */
    private $viewId;

    /**
     * @var callable
     */
    private $onQueryMade;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $currentBackoffTime = self::DEFAULT_MIN_BACKOFF_TIME;

    private $pingMysqlEverySecs;

    /**
     * @var GoogleQueryObjectFactory
     */
    private $googleQueryObjectFactory;

    /**
     * @var GoogleMetricMapper
     */
    private $metricMapper;

    /**
     * @var string
     */
    private $quotaUser;

    public function __construct(\Google_Service_AnalyticsReporting $gaService, $viewId, array $goalsMapping, $idSite, $quotaUser,
                                GoogleQueryObjectFactory $googleQueryObjectFactory, LoggerInterface $logger)
    {
        $this->gaService = $gaService;
        $this->viewId = $viewId;
        $this->logger = $logger;
        $this->googleQueryObjectFactory = $googleQueryObjectFactory;
        $this->pingMysqlEverySecs = StaticContainer::get('GoogleAnalyticsImporter.pingMysqlEverySecs') ?: self::PING_MYSQL_EVERY;
        $this->metricMapper = new GoogleMetricMapper(Site::isEcommerceEnabledFor($idSite), $goalsMapping);
        $this->quotaUser = $quotaUser;
    }

    public function query(Date $day, array $dimensions, array $metrics, array $options = [])
    {
        $this->metricMapper->setCustomMappings(isset($options['mappings']) ? $options['mappings'] : []);

        $gaMetricsToQuery = $this->metricMapper->getMappedMetrics($metrics);

        $dataTableFactory = new GoogleResponseDataTableFactory($dimensions, $metrics, $gaMetricsToQuery);

        // detect the metric used to order result sets. we need to send this metric with each partial request to ensure correct order.
        $orderByMetric = $this->googleQueryObjectFactory->getOrderByMetric($gaMetricsToQuery, $options);

        foreach (array_chunk($gaMetricsToQuery, 9) as $chunk) {
            $chunkResponse = $this->gaBatchGet($day, array_values($chunk), array_merge(['dimensions' => $dimensions], $options), $orderByMetric);

            // some metric/date combinations seem to cause GA to return absolutely nothing (no rows + NULL row count).
            // in this case we remove the problematic metrics and try again.
            if ($chunkResponse->getReports()[0]->getData()->getRowCount() === null) {
                $chunk = array_diff($chunk, self::$problematicMetrics);
                if (empty($chunk)) {
                    continue;
                }

                $chunkResponse = $this->gaBatchGet($day, $chunk, array_merge(['dimensions' => $dimensions], $options), $orderByMetric);

                // the second request can still fail, in which case repeated requests tend to still fail. so we ignore this data. seems to only
                // happen for old data anyway.
                if ($chunkResponse->getReports()[0]->getData()->getRowCount() === null) {
                    continue;
                }
            }

            if ($this->onQueryMade) {
                $callable = $this->onQueryMade;
                $callable();
            }

            usleep(100 * 1000);

            $dataTableFactory->mergeGaResponse($chunkResponse, $chunk);
        }

        $dataTableFactory->convertGaColumnsToMetricIndexes($this->metricMapper->getMappings());

        return $dataTableFactory->getDataTable();
    }

    private function gaBatchGet(Date $date, $metricNamesChunk, $options, $orderByMetric)
    {
        if (!in_array($orderByMetric, $metricNamesChunk)) {
            $metricNamesChunk[] = $orderByMetric; // make sure the order by metric is included in this query so we can sort
        }

        if (!isset($options['orderBys'])) {
            $options['orderBys'][] = [
                'field' => $orderByMetric,
                'order' => 'descending',
            ];
        }

        $request = $this->googleQueryObjectFactory->make($this->viewId, $date, $metricNamesChunk, $options);

        $lastGaError = null;
        $this->currentBackoffTime = self::DEFAULT_MIN_BACKOFF_TIME;

        $attempts = 0;
        while ($attempts < $this->maxAttempts) {
            try {
                $this->issuePointlessMysqlQuery();

                $result = $this->gaService->reports->batchGet($request, [
                    'quotaUser' => $this->quotaUser,
                ]);

                if (empty($result)) {
                    ++$attempts;

                    $this->backOff();

                    $this->logger->info("Google Analytics API returned null for some reason, trying again...");

                    continue;
                }

                return $result;
            } catch (\Exception $ex) {
                $this->logger->debug("Google Analytics returned an error: {message}", [
                    'message' => $ex->getMessage(),
                ]);

                if ($ex->getCode() == 403 || $ex->getCode() == 429) {
                    if (strpos($ex->getMessage(), 'daily') !== false) {
                        throw new DailyRateLimitReached();
                    }

                    ++$attempts;

                    $this->logger->debug("Waiting {$this->currentBackoffTime}s before trying again...");

                    $this->backOff();
                } else if ($ex->getCode() >= 500) {
                    ++$attempts;

                    $this->logger->info("Google Analytics API returned error: {$ex->getMessage()}. Waiting one minute before trying again...");

                    $messageContent = @json_decode($ex->getMessage(), true);
                    if (isset($messageContent['error']['message'])) {
                        $lastGaError = $messageContent['error']['message'];
                    }

                    $this->backOff();
                } else {
                    throw $ex;
                }
            }
        }

        $message = "Failed to reach GA after " . $this->maxAttempts . " attempts. Restart the import later.";
        if (!empty($lastGaError)) {
            $message .= ' Last GA error message: ' . $lastGaError;
        }
        throw new \Exception($message);
    }

    /**
     * @param callable $onQueryMade
     */
    public function setOnQueryMade($onQueryMade)
    {
        $this->onQueryMade = $onQueryMade;
    }

    /**
     * @param int $maxAttempts
     */
    public function setMaxAttempts($maxAttempts)
    {
        $this->maxAttempts = $maxAttempts;
    }

    /**
     * Used to keep the mysql connection alive in case GA API makes us wait for too long.
     */
    private function issuePointlessMysqlQuery()
    {
        Db::fetchOne("SELECT COUNT(*) FROM `" . Common::prefixTable('option') . "`");
    }

    private function sleep($time)
    {
        $amountSlept = 0;
        while ($amountSlept < $time) {
            $timeToSleep = min($this->pingMysqlEverySecs, $time - $amountSlept);

            sleep($timeToSleep);
            $amountSlept += $timeToSleep;

            $this->issuePointlessMysqlQuery();
        }
    }

    private function backOff()
    {
        $this->sleep($this->currentBackoffTime);
        $this->currentBackoffTime = min(self::MAX_BACKOFF_TIME, $this->currentBackoffTime * 2);
    }
}
