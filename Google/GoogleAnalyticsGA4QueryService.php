<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\Google;

use Google\Analytics\Admin\V1alpha\AnalyticsAdminServiceClient;
use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Db;
use Piwik\Piwik;
use Piwik\Site;
use Psr\Log\LoggerInterface;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleGA4MetricMapper;

class GoogleAnalyticsGA4QueryService
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
     * @var BetaAnalyticsDataClient
     */
    private $gaClient;

    /**
     * @var AnalyticsAdminServiceClient
     */
    private $gaAdminClient;

    /**
     * @var string
     */
    private $propertyId;

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
     * @var GoogleGA4QueryObjectFactory
     */
    private $googleGA4QueryObjectFactory;

    /**
     * @var GoogleMetricMapper
     */
    private $metricMapper;

    /**
     * @var string
     */
    private $quotaUser;

    public function __construct(BetaAnalyticsDataClient $gaClient,AnalyticsAdminServiceClient $gaAdminClient, $propertyId, array $goalsMapping, $idSite, $quotaUser,
                                GoogleGA4QueryObjectFactory $googleGA4QueryObjectFactory, LoggerInterface $logger)
    {
        $this->gaClient = $gaClient;
        $this->gaAdminClient = $gaAdminClient;
        $this->propertyId = $propertyId;
        $this->logger = $logger;
        $this->googleGA4QueryObjectFactory = $googleGA4QueryObjectFactory;
        $this->pingMysqlEverySecs = StaticContainer::get('GoogleAnalyticsImporter.pingMysqlEverySecs') ?: self::PING_MYSQL_EVERY;
        $this->metricMapper = new GoogleGA4MetricMapper(Site::isEcommerceEnabledFor($idSite), $goalsMapping);
        $this->quotaUser = $quotaUser;
    }

    public function query(Date $day, array $dimensions, array $metrics, array $options = [])
    {
        $this->metricMapper->setCustomMappings(isset($options['mappings']) ? $options['mappings'] : []);

        $gaMetricsToQuery = $this->metricMapper->getMappedMetrics($metrics);

        $dataTableFactory = new GoogleGA4ResponseDataTableFactory($dimensions, $metrics, $gaMetricsToQuery);

        // detect the metric used to order result sets. we need to send this metric with each partial request to ensure correct order.
        $orderByMetric = $this->googleGA4QueryObjectFactory->getOrderByMetric($gaMetricsToQuery, $options);

        $response = $this->gaRunReport($day, array_values($gaMetricsToQuery), array_merge(['dimensions' => $dimensions], $options), $orderByMetric);

        if ($response->getRowCount()) {
            if ($this->onQueryMade) {
                $callable = $this->onQueryMade;
                $callable();
            }

            $dataTableFactory->mergeGaResponse($response, $gaMetricsToQuery);

            $dataTableFactory->convertGaColumnsToMetricIndexes($this->metricMapper->getMappings());
        }

        return $dataTableFactory->getDataTable();
    }

    private function gaRunReport(Date $date, $metricNamesChunk, $options, $orderByMetric)
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

        $request = $this->googleGA4QueryObjectFactory->make($this->propertyId, $date, $metricNamesChunk, $options);

        $lastGaError = null;
        $this->currentBackoffTime = self::DEFAULT_MIN_BACKOFF_TIME;

        $attempts = 0;
        while ($attempts < $this->maxAttempts) {
            try {
                $this->issuePointlessMysqlQuery();

                $result = $this->gaClient->runReport($request);

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

                $messageContent = @json_decode($ex->getMessage(), true);
                if (isset($messageContent['error']['message'])) {
                    $lastGaError = $messageContent['error']['message'];
                }

                /**
                 * @ignore
                 */
                Piwik::postEvent('GoogleAnalyticsImporter.onApiError', [$ex]);

                if ($ex->getCode() == 403 || $ex->getCode() == 429) {
                    if (strpos($ex->getMessage(), 'daily') !== false) {
                        throw new DailyRateLimitReached();
                    }

                    ++$attempts;

                    $this->logger->debug("Waiting {$this->currentBackoffTime}s before trying again...");

                    $this->backOff();
                } else if ($this->isIgnorableException($ex)) {
                    ++$attempts;

                    $this->logger->info("Google Analytics API returned an ignorable or temporary error: {$ex->getMessage()}. Waiting {$this->currentBackoffTime}s before trying again...");

                    $this->backOff();
                } else if ($ex->getCode() >= 500) {
                    ++$attempts;

                    $this->logger->info("Google Analytics API returned error: {$ex->getMessage()}. Waiting {$this->currentBackoffTime}s before trying again...");

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

    private function isIgnorableException(\Exception $ex)
    {
        if ($ex->getCode() !== 400) {
            return false;
        }

        $messageContent = @json_decode($ex->getMessage(), true);
        if (empty($messageContent['error']['message'])) {
            return false;
        }

        if (strpos($messageContent['error']['message'], 'Unknown metric') === 0) {
            return true;
        }

        return false;
    }
}
