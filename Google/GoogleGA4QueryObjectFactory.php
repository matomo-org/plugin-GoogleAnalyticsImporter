<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\Google;

use Piwik\Config;
use Piwik\Date;
use Piwik\Plugins\GoogleAnalyticsImporter\ImporterGA4;
use Piwik\Log\LoggerInterface;
class GoogleGA4QueryObjectFactory
{
    private static $defaultMetricOrderByPriority = [
        //        'ga:uniquePageviews', Not available in GA4
        //        'ga:uniqueScreenviews', Not available in GA4
        'screenPageViews',
        'sessions',
        'conversions',
    ];
    /**
     * @var LoggerInterface
     */
    private $logger;
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    public function make($propertyID, Date $date, $metricNames, $options, $streamIds = [])
    {
        $dimensionNames = !empty($options['dimensions']) ? $options['dimensions'] : [];
        $dimensionFilter = '';
        if (!empty($options['dimensionFilter'])) {
            $dimensionFilter = $this->makeGaDimensionFilter($options['dimensionFilter'], $streamIds);
        }
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
        $body = ['property' => $propertyID, 'dateRanges' => [$this->makeGaDateRange($date)], 'returnPropertyQuota' => \true];
        if (!empty($options['orderBys'])) {
            $this->checkOrderBys($options['orderBys'], $metricNames, $dimensionNames);
            $body['orderBys'] = [$this->makeGaOrderBys($options['orderBys'], $metricNames, $dimensionNames)];
        }
        if (!empty($dimensions)) {
            $body['dimensions'] = $dimensions;
        }
        if (!empty($metrics)) {
            $body['metrics'] = $metrics;
        }
        if (!empty($dimensionFilter)) {
            $body['dimensionFilter'] = $dimensionFilter;
        }
        //no need to set any limit since for tests a smaller data is sufficient
        if (!defined('PIWIK_TEST_MODE')) {
            $body['limit'] = (string) ImporterGA4::PAGE_SIZE;
        }
        $body['keepEmptyRows'] = \true;
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
    //TODO: Need to change as per GA4
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
        return new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\DateRange(['start_date' => $dateStr, 'end_date' => $dateStr]);
    }
    private function makeGaSegmentDimension()
    {
        $segmentDimensions = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\AnalyticsReporting\Dimension();
        $segmentDimensions->setName("ga:segment");
        return $segmentDimensions;
    }
    private function makeGaDimensionFilter($gaDimensionFilter, $streamIds)
    {
        $filterExpressions = [];
        switch ($gaDimensionFilter['filterType']) {
            case 'inList':
                $filterType = array('field_name' => $gaDimensionFilter['dimension'], 'in_list_filter' => new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\Filter\InListFilter(['values' => $gaDimensionFilter['filterValue'], 'case_sensitive' => \false]));
                break;
        }
        if (!empty($filterType)) {
            $filterExpressions[] = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\FilterExpression(['filter' => new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\Filter($filterType)]);
        }
        if (!empty($streamIds)) {
            $filterExpressions[] = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\FilterExpression(['filter' => new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\Filter(array('field_name' => 'streamId', 'in_list_filter' => new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\Filter\InListFilter(['values' => $streamIds, 'case_sensitive' => \false])))]);
        }
        if (!empty($filterExpressions)) {
            return new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\FilterExpression(['and_group' => new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\FilterExpressionList(['expressions' => $filterExpressions])]);
        }
        return [];
    }
    private function makeGaDimension($gaDimension)
    {
        return new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\Dimension(['name' => $gaDimension]);
    }
    private function makeGaMetric($gaMetric)
    {
        return new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\Metric(['name' => $gaMetric]);
    }
    private function makeGaOrderBys($orderBys, $metricNames, $dimensionNames)
    {
        $orderByInfo = $orderBys[0];
        $order = strtoupper($orderByInfo['order']);
        $data = ['desc' => $order == 'DESC'];
        if (in_array($orderByInfo['field'], $metricNames)) {
            $metric = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\OrderBy\MetricOrderBy();
            $metric->setMetricName($orderByInfo['field']);
            $data['metric'] = $metric;
        } else {
            $dimension = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\OrderBy\DimensionOrderBy();
            $dimension->setDimensionName($orderByInfo['field']);
            $data['dimension'] = $dimension;
        }
        return new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\OrderBy($data);
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
