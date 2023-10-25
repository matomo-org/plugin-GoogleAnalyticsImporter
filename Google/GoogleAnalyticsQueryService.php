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
use Piwik\Date;
use Piwik\Db;
use Piwik\Exception\Exception;
use Piwik\Option;
use Piwik\Piwik;
use Piwik\Site;
use Piwik\Log\LoggerInterface;
class GoogleAnalyticsQueryService
{
    const DEFAULT_MAX_ATTEMPTS = 30;
    const MAX_BACKOFF_TIME = 60;
    const PING_MYSQL_EVERY = 25;
    const DEFAULT_MIN_BACKOFF_TIME = 2;
    // start at 2s since GA seems to have trouble w/ the 10 requests per 100s limit w/ 1
    const DELAY_OPTION_NAME = 'GoogleAnalyticsImporter_nextAvailableAt';
    private static $problematicMetrics = ['ga:users', 'ga:hits'];
    /**
     * @var int
     */
    private $maxAttempts = self::DEFAULT_MAX_ATTEMPTS;
    /**
     * @var \Google\Service\Analytics
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
    private $skipAttemptForExceptionCodes = [401, 403];
    private $singleAttemptForExceptionCodes = [500, 503];
    public function __construct(\Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\AnalyticsReporting $gaService, $viewId, array $goalsMapping, $idSite, $quotaUser, \Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleQueryObjectFactory $googleQueryObjectFactory, LoggerInterface $logger)
    {
        $this->gaService = $gaService;
        $this->viewId = $viewId;
        $this->logger = $logger;
        $this->googleQueryObjectFactory = $googleQueryObjectFactory;
        $this->pingMysqlEverySecs = StaticContainer::get('GoogleAnalyticsImporter.pingMysqlEverySecs') ?: self::PING_MYSQL_EVERY;
        $this->metricMapper = new \Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleMetricMapper(Site::isEcommerceEnabledFor($idSite), $goalsMapping);
        $this->quotaUser = $quotaUser;
    }
    public function query(Date $day, array $dimensions, array $metrics, array $options = [])
    {
        $this->metricMapper->setCustomMappings(isset($options['mappings']) ? $options['mappings'] : []);
        $gaMetricsToQuery = $this->metricMapper->getMappedMetrics($metrics);
        $dataTableFactory = new \Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleResponseDataTableFactory($dimensions, $metrics, $gaMetricsToQuery);
        // detect the metric used to order result sets. we need to send this metric with each partial request to ensure correct order.
        $orderByMetric = $this->googleQueryObjectFactory->getOrderByMetric($gaMetricsToQuery, $options);
        foreach (array_chunk($gaMetricsToQuery, 9) as $chunk) {
            $chunkResponse = $this->gaBatchGet($day, array_values($chunk), array_merge(['dimensions' => $dimensions], $options), $orderByMetric);
            if ($this->onQueryMade) {
                $callable = $this->onQueryMade;
                $callable();
            }
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
            usleep(100 * 1000);
            $dataTableFactory->mergeGaResponse($chunkResponse, $chunk);
        }
        $dataTableFactory->convertGaColumnsToMetricIndexes($this->metricMapper->getMappings());
        return $dataTableFactory->getDataTable();
    }
    private function gaBatchGet(Date $date, $metricNamesChunk, $options, $orderByMetric)
    {
        if (!in_array($orderByMetric, $metricNamesChunk)) {
            $metricNamesChunk[] = $orderByMetric;
            // make sure the order by metric is included in this query so we can sort
        }
        if (!isset($options['orderBys'])) {
            $options['orderBys'][] = ['field' => $orderByMetric, 'order' => 'descending'];
        }
        $request = $this->googleQueryObjectFactory->make($this->viewId, $date, $metricNamesChunk, $options);
        $lastGaError = null;
        $this->currentBackoffTime = self::DEFAULT_MIN_BACKOFF_TIME;
        $attempts = 0;
        $skipReAttempt = \false;
        while ($attempts < $this->maxAttempts) {
            try {
                $this->issuePointlessMysqlQuery();
                $result = $this->gaService->reports->batchGet($request, ['quotaUser' => $this->quotaUser]);
                // @TODO if result->reports[0]->nextPageToken returns a value
                //       then there is more data and the request must be made
                //       made again with a an added parameter pageToken=nextPageToken
                //       result->reports[0]->data->rowCount contains the totals
                if (empty($result)) {
                    ++$attempts;
                    $this->backOff($skipReAttempt);
                    $this->logger->info("Google Analytics API returned null for some reason, trying again...");
                    continue;
                }
                return $result;
            } catch (\Exception $ex) {
                $skipReAttempt = in_array($ex->getCode(), $this->skipAttemptForExceptionCodes);
                $this->logger->debug("Google Analytics returned an error: {message}", ['message' => $ex->getMessage()]);
                $messageContent = @json_decode($ex->getMessage(), \true);
                if (isset($messageContent['error']['message'])) {
                    $lastGaError = $messageContent['error']['message'];
                } else {
                    $lastGaError = $ex->getMessage();
                }
                /**
                 * @ignore
                 */
                Piwik::postEvent('GoogleAnalyticsImporter.onApiError', [$ex]);
                if ($ex->getCode() == 403 || $ex->getCode() == 429) {
                    if (strpos($ex->getMessage(), 'daily') !== \false) {
                        $this->setDbBackOff('D');
                        throw new \Piwik\Plugins\GoogleAnalyticsImporter\Google\DailyRateLimitReached();
                    } else {
                        if (stripos($ex->getMessage(), 'hour') !== \false) {
                            $this->setDbBackOff();
                        }
                    }
                    ++$attempts;
                    $this->logger->debug("Waiting {$this->currentBackoffTime}s before trying again...");
                    $this->backOff($skipReAttempt);
                } else {
                    if ($this->isIgnorableException($ex)) {
                        ++$attempts;
                        $this->logger->info("Google Analytics API returned an ignorable or temporary error: {$ex->getMessage()}. Waiting {$this->currentBackoffTime}s before trying again...");
                        $this->backOff($skipReAttempt);
                    } else {
                        if ($ex->getCode() >= 500) {
                            ++$attempts;
                            $this->logger->info("Google Analytics API returned error: {$ex->getMessage()}. Waiting {$this->currentBackoffTime}s before trying again...");
                            $backoff = \false;
                            if (in_array($ex->getCode(), $this->singleAttemptForExceptionCodes)) {
                                $this->maxAttempts = 2;
                                $backoff = $attempts === 2;
                            }
                            $this->backOff($backoff);
                        } else {
                            throw $ex;
                        }
                    }
                }
                if ($skipReAttempt) {
                    $this->maxAttempts = 1;
                    $this->logger->debug("Skipping Reattempt, due to following exception status code " . $ex->getCode());
                    break;
                }
            }
        }
        $message = "Failed to reach GA after " . $this->maxAttempts . " attempt(s). The import will automatically restart later and you don't need to do anything.";
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
    private function backOff($isSkipReAttempt = \false)
    {
        if ($isSkipReAttempt) {
            return;
        }
        $this->sleep($this->currentBackoffTime);
        $this->currentBackoffTime = min(self::MAX_BACKOFF_TIME, $this->currentBackoffTime * 2);
    }
    private function isIgnorableException(\Exception $ex)
    {
        if ($ex->getCode() !== 400) {
            return \false;
        }
        $messageContent = @json_decode($ex->getMessage(), \true);
        if (empty($messageContent['error']['message'])) {
            return \false;
        }
        if (strpos($messageContent['error']['message'], 'Unknown metric') === 0) {
            return \true;
        }
        return \false;
    }
    public function setDbBackOff($backoffLength = 'H')
    {
        $nextRetry = Date::factory('+1 hour')->getTimestamp();
        if ($backoffLength === 'D') {
            $nextRetry = Date::factory('tomorrow')->getTimestamp();
        }
        Option::set(self::DELAY_OPTION_NAME, $nextRetry);
    }
}
