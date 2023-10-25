<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\Google;

use Piwik\Date;
use Piwik\Plugins\GoogleAnalyticsImporter\Importer;
use Piwik\Log\LoggerInterface;
class GoogleQueryObjectFactory
{
    private static $defaultMetricOrderByPriority = ['ga:uniquePageviews', 'ga:uniqueScreenviews', 'ga:pageviews', 'ga:screenviews', 'ga:sessions', 'ga:goalCompletionsAll'];
    /**
     * @var LoggerInterface
     */
    private $logger;
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    public function make($viewId, Date $date, $metricNames, $options)
    {
        $dimensionNames = !empty($options['dimensions']) ? $options['dimensions'] : [];
        $dimensions = [];
        foreach ($dimensionNames as $gaDimension) {
            $dimensions[] = $this->makeGaDimension($gaDimension);
        }
        $segments = [];
        if (!empty($options['segment'])) {
            $segments[] = $this->makeGaSegment($options['segment']);
            $dimensions[] = $this->makeGaSegmentDimension();
        }
        $metricNames = array_values($metricNames);
        $metrics = array_map(function ($name) {
            return $this->makeGaMetric($name);
        }, $metricNames);
        $request = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\AnalyticsReporting\ReportRequest();
        $request->setViewId($viewId);
        $request->setDateRanges([$this->makeGaDateRange($date)]);
        $request->setDimensions($dimensions);
        $request->setSegments($segments);
        $request->setMetrics($metrics);
        //no need to set any limit since for tests a smaller data is sufficient
        if (!defined('PIWIK_TEST_MODE')) {
            $request->setPageSize(Importer::PAGE_SIZE);
        }
        if (!empty($options['orderBys'])) {
            $this->checkOrderBys($options['orderBys'], $metricNames, $dimensionNames);
            $request->setOrderBys($this->makeGaOrderBys($options['orderBys']));
        }
        $getReport = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\AnalyticsReporting\GetReportsRequest();
        $getReport->setReportRequests([$request]);
        $body = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\AnalyticsReporting\GetReportsRequest();
        $body->setReportRequests([$request]);
        return $body;
    }
    public function getOrderByMetric($gaMetricsToQuery, $queryOptions)
    {
        $orderByMetric = null;
        if (!empty($queryOptions['orderBys'])) {
            // NOTE: this only allows sorting by one metric at a time, but that should be ok
            foreach ($queryOptions['orderBys'] as $entry) {
                if (in_array($entry['field'], $gaMetricsToQuery)) {
                    return $entry['field'];
                }
            }
        }
        foreach (self::$defaultMetricOrderByPriority as $metricName) {
            if (in_array($metricName, $gaMetricsToQuery)) {
                return $metricName;
            }
        }
        throw new \Exception("Not sure what metric to use to order results, got: " . implode(', ', $gaMetricsToQuery));
    }
    private function makeGaSegment($segment)
    {
        $segmentObj = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\AnalyticsReporting\Segment();
        if (isset($segment['segmentId'])) {
            $segmentObj->setSegmentId($segment['segmentId']);
        } else {
            $segmentObj->setDynamicSegment($segment['dynamicSegment']);
        }
        return $segmentObj;
    }
    private function makeGaDateRange(Date $date)
    {
        $dateStr = $date->toString();
        $dateRange = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\AnalyticsReporting\DateRange();
        $dateRange->setStartDate($dateStr);
        $dateRange->setEndDate($dateStr);
        return $dateRange;
    }
    private function makeGaSegmentDimension()
    {
        $segmentDimensions = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\AnalyticsReporting\Dimension();
        $segmentDimensions->setName("ga:segment");
        return $segmentDimensions;
    }
    private function makeGaDimension($gaDimension)
    {
        $result = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\AnalyticsReporting\Dimension();
        $result->setName($gaDimension);
        return $result;
    }
    private function makeGaMetric($gaMetric)
    {
        $metric = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\AnalyticsReporting\Metric();
        $metric->setExpression($gaMetric);
        return $metric;
    }
    private function makeGaOrderBys($orderBys)
    {
        $gaOrderBys = [];
        foreach ($orderBys as $orderByInfo) {
            $orderBy = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\AnalyticsReporting\OrderBy();
            $orderBy->setFieldName($orderByInfo['field']);
            $orderBy->setOrderType('VALUE');
            $order = strtoupper($orderByInfo['order']);
            if ($order == 'DESC') {
                $order = 'DESCENDING';
            } else {
                if ($order == 'ASC') {
                    $order = 'ASCENDING';
                }
            }
            $orderBy->setSortOrder($order);
            $gaOrderBys[] = $orderBy;
        }
        return $gaOrderBys;
    }
    private function checkOrderBys($orderBys, array $metricsQueried, array $dimensions)
    {
        foreach ($orderBys as $entry) {
            $field = $entry['field'];
            if (!in_array($field, $metricsQueried) && !in_array($field, $dimensions)) {
                $this->logger->error("Unexpected error: trying to order by {field}, but field is not in list of metrics/dimensions being queried: {metrics}/{dims}", ['field' => $field, 'metrics' => implode(', ', $metricsQueried), 'dims' => implode(', ', $dimensions)]);
            }
        }
    }
}
