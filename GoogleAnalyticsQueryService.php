<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter;

use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Date;
use Piwik\Metrics;
use Piwik\Tracker\Action;

class GoogleAnalyticsQueryService
{
    const MAX_ATTEMPTS = 100;

    /**
     * @var \Google_Service_Analytics
     */
    private $gaService;

    /**
     * @var string
     */
    private $viewId;

    /**
     * @var array
     */
    private $mapping;

    /**
     * @var array
     */
    private $goalsMapping;

    /**
     * @var int
     */
    private $idSite;

    public function __construct(\Google_Service_AnalyticsReporting $gaService, $viewId, array $goalsMapping, $idSite)
    {
        $this->gaService = $gaService;
        $this->viewId = $viewId;
        $this->goalsMapping = $goalsMapping;
        $this->idSite = $idSite;

        $this->mapping = $this->getMetricIndicesToGaMetrics();
    }

    public function query(Date $day, array $dimensions, array $metrics, array $options = [])
    {
        $mappings = $this->mapping;
        if (!empty($options['mappings'])) {
            $mappings = array_merge($mappings, $options['mappings']);
        }

        $queries = $this->getQueriesToMake($metrics, $mappings);

        $date = $day->toString();

        $result = new DataTable();
        foreach ($queries as $query) {
            $gaMetrics = $query['metrics'];
            $queryOptions = isset($query['options']) ? $query['options'] : [];

            $metricNames = [];
            foreach ($gaMetrics as $metricsList) {
                foreach ($metricsList as $gaMetric) {
                    $metricNames[] = $gaMetric;
                }
            }

            // TODO: count api queries made in the import command
            $metricNames = array_unique($metricNames);

            foreach (array_chunk($metricNames, 9) as $chunk) {
                $chunkResponse = $this->gaBatchGet($date, $chunk, array_merge(['dimensions' => $dimensions], $queryOptions, $options));

                usleep(100 * 1000);

                $this->mergeResult($result, $chunkResponse, $gaMetrics, $dimensions, $chunk);
            }
        }
        $this->convertGaColumnsToMetricIndexes($result, $metrics, $mappings);

        return $result;
    }

    private function convertGaColumnsToMetricIndexes(DataTable $result, $metrics, $mappings)
    {
        $metricNames = [];
        foreach ($metrics as $metricIndex) {
            if (is_array($mappings[$metricIndex])) {
                $metricInfo = $mappings[$metricIndex];
                if (is_array($metricInfo['metric'])) {
                    $metricNames[$metricIndex] = $metricInfo['metric'][0];
                } else {
                    $metricNames[$metricIndex] = $metricInfo['metric'];
                }
            } else {
                $metricNames[$metricIndex] = $mappings[$metricIndex];
            }
        }

        foreach ($result->getRows() as $row) {
            $newColumns = ['label' => $row->getColumn('label')];
            foreach ($metrics as $metricIndex) {
                $gaMetricName = $metricNames[$metricIndex];
                $value = $row->getColumn($gaMetricName);

                if ($value !== false
                    && isset($mappings[$metricIndex]['calculate'])
                ) {
                    $fn = $mappings[$metricIndex]['calculate'];
                    $value = $fn($row);
                }

                if ($value !== false) {
                    $newColumns[$metricIndex] = $value;
                }
            }
            $row->setColumns($newColumns);
        }
        $result->setLabelsHaveChanged();
    }

    private function getQueriesToMake($metrics, $mappings)
    {
        $queriesBySegment = [];
        foreach ($metrics as $index) {
            if (!isset($mappings[$index])) {
                throw new \Exception("Don't know how to map metric index ${index} to GA metric.");
            }

            $gaMetric = $mappings[$index];

            $segment = '';
            $metric = $gaMetric;
            if (isset($gaMetric['segment'])) {
                $queriesBySegment[$segment]['options'] = [
                    'segment' => $segment,
                ];

                $segment = json_encode($gaMetric['segment']);
            }

            if (isset($gaMetric['metric'])) {
                $metric = $gaMetric['metric'];
            }

            if (!is_array($metric)) {
                $metric = [$metric];
            }

            $queriesBySegment[$segment]['metrics'][$index] = $metric;
        }

        return array_values($queriesBySegment);
    }

    // TODO: can probably make some of this code more efficient, and made more clear (it's very not clear).
    private function mergeResult(DataTable $table, \Google_Service_AnalyticsReporting_GetReportsResponse $response, $gaMetrics, $gaDimensions, $metricsQueried)
    {
        /** @var \Google_Service_AnalyticsReporting_Report $gaReport */
        foreach ($response->getReports() as $gaReport) {
            /** @var \Google_Service_AnalyticsReporting_ReportRow $gaRow */
            foreach ($gaReport->getData()->getRows() as $gaRow) {
                $tableRow = new DataTable\Row();

                $label = [];
                foreach (array_values($gaDimensions) as $index => $dimension) {
                    $labelValue = $gaRow->dimensions[$index] == '(not set)' ? null : $gaRow->dimensions[$index];
                    $tableRow->setMetadata($dimension, $labelValue);

                    $label[$dimension] = $labelValue;
                }

                if (!empty($label)) {
                    $label = implode(',', $label); // so we can call getRowFromLabel()
                    $tableRow->setColumn('label', $label);
                }

                $gaRowMetrics = $gaRow->getMetrics()[0]->getValues();
                $gaRowMetrics = array_combine($metricsQueried, $gaRowMetrics);

                foreach ($gaRowMetrics as $name => $value) {
                    $tableRow->setColumn($name, $value);
                }

                $existingRow = empty($label) ? $table->getFirstRow() : $table->getRowFromLabel($label);
                if (!empty($existingRow)) {
                    $existingRow->sumRow($tableRow);
                } else {
                    $table->addRow($tableRow);
                }
            }
        }
    }

    public function getMetricIndicesToGaMetrics() // TODO: Move to GoogleMetrics class or something
    {
        $goalSpecificMetrics = array_values($this->getEcommerceMetricIndicesToGaMetrics());
        foreach ($this->goalsMapping as $idGoal => $gaIdGoal) {
            $goalSpecificMetrics = array_merge($goalSpecificMetrics, array_values($this->getGoalSpecificMetricIndicesToGametrics($gaIdGoal)));
        }
        $goalSpecificMetrics[] = 'ga:sessions'; // for nb_visits_converted

        return [
            // visit metrics
            Metrics::INDEX_NB_UNIQ_VISITORS => 'ga:users',
            Metrics::INDEX_NB_VISITS => 'ga:sessions',
            Metrics::INDEX_NB_ACTIONS => 'ga:hits',
            Metrics::INDEX_SUM_VISIT_LENGTH => [
                'metric' => 'ga:sessionDuration',
                'calculate' => function (Row $row) {
                    return floor($row->getColumn('ga:sessionDuration'));
                },
            ],
            Metrics::INDEX_BOUNCE_COUNT => 'ga:bounces',

            // TODO: goalConversionRateAll doesn't seem to include ecommerce orders. not sure how to make it accurate in this case...
            Metrics::INDEX_NB_VISITS_CONVERTED => [
                'metric' => ['ga:goalConversionRateAll', 'ga:sessions'],
                'calculate' => function (Row $row) {
                    return floor(self::getQuotientFromPercentage($row->getColumn('ga:goalConversionRateAll') * $row->getColumn('ga:sessions')));
                },
            ],

            // conversion aware
            Metrics::INDEX_NB_CONVERSIONS => [
                'metric' => ['ga:goalCompletionsAll', 'ga:transactions'],
                'calculate' => function (Row $row) {
                    return $row->getColumn('ga:goalCompletionsAll') + $row->getColumn('ga:transactions');
                },
            ],
            Metrics::INDEX_REVENUE => 'ga:totalValue',

            // goal specific
            Metrics::INDEX_GOALS => [
                'metric' => $goalSpecificMetrics,
                'calculate' => function ($metrics) {
                    return $this->createGoalSpecificMetricArray($metrics);
                },
            ],

            // actions
            Metrics::INDEX_PAGE_NB_HITS => 'ga:pageviews',
            Metrics::INDEX_PAGE_SUM_TIME_SPENT => [
                'metric' => 'ga:timeOnPage',
                'calculate' => function (Row $row) {
                    return round($row->getColumn('ga:timeOnPage'));
                },
            ],

            // actions (requires correct dimension)
            Metrics::INDEX_PAGE_EXIT_NB_UNIQ_VISITORS => 'ga:users',
            Metrics::INDEX_PAGE_EXIT_NB_VISITS => 'ga:sessions',

            // actions (requires correct dimension)
            Metrics::INDEX_PAGE_ENTRY_NB_UNIQ_VISITORS => 'ga:users',
            Metrics::INDEX_PAGE_ENTRY_NB_VISITS => 'ga:sessions',
            Metrics::INDEX_PAGE_ENTRY_NB_ACTIONS => 'ga:hits',
            Metrics::INDEX_PAGE_ENTRY_SUM_VISIT_LENGTH => [
                'metric' => 'ga:sessionDuration',
                'calculate' => function (Row $row) {
                    return floor($row->getColumn('ga:sessionDuration'));
                },
            ],
            Metrics::INDEX_PAGE_ENTRY_BOUNCE_COUNT => 'ga:bounces',

            // actions (requires correct dimensions)
            Metrics::INDEX_PAGE_IS_FOLLOWING_SITE_SEARCH_NB_HITS => 'ga:hits',

            Metrics::INDEX_PAGE_SUM_TIME_GENERATION => 'ga:pageDownloadTime',
            Metrics::INDEX_PAGE_NB_HITS_WITH_TIME_GENERATION => 'ga:pageLoadSample',

            // ecommerce item metrics (requires correct dimensions)
            Metrics::INDEX_ECOMMERCE_ITEM_REVENUE => 'ga:itemRevenue',
            Metrics::INDEX_ECOMMERCE_ITEM_QUANTITY => 'ga:itemQuantity',
            Metrics::INDEX_ECOMMERCE_ITEM_PRICE => 'ga:revenuePerItem', // TODO: not sure how accurate this is
            Metrics::INDEX_ECOMMERCE_ORDERS => 'ga:uniquePurchases', // TODO: is this right? I think so, not sure tough
        ];
    }

    private function createGoalSpecificMetricArray(Row $row)
    {
        $result = [];
        foreach ($this->goalsMapping as $idGoal => $gaIdGoal) {
            $goalSpecificMetrics = $this->getGoalSpecificMetricIndicesToGametrics($gaIdGoal);

            $innerColumns = [];
            foreach ($goalSpecificMetrics as $index => $gaName) {
                if ($index == Metrics::INDEX_GOAL_NB_VISITS_CONVERTED
                    && $row->getColumn($gaName) !== false
                ) {
                    $value = floor(self::getQuotientFromPercentage($row->getColumn($gaName)) * $row->getColumn('ga:sessions')); // TODO: code redundancy w/ above
                } else {
                    $value = $row->getColumn($gaName);
                }

                if ($value !== false) {
                    $innerColumns[$index] = $value;
                }
            }
            $result[$idGoal] = $innerColumns;
        }
        return $result;
    }

    public function getEcommerceMetricIndicesToGaMetrics()
    {
        return [
            Metrics::INDEX_GOAL_ECOMMERCE_REVENUE_SUBTOTAL => 'ga:transactionRevenue',
            Metrics::INDEX_GOAL_ECOMMERCE_REVENUE_TAX => 'ga:transactionTax',
            Metrics::INDEX_GOAL_ECOMMERCE_REVENUE_SHIPPING => 'ga:transactionShipping',
            Metrics::INDEX_GOAL_ECOMMERCE_ITEMS => 'ga:itemQuantity',
        ];
    }

    public function getGoalSpecificMetricIndicesToGametrics($gaIdGoal)
    {
        return [
            Metrics::INDEX_GOAL_NB_CONVERSIONS => "ga:goal{$gaIdGoal}Completions",
            Metrics::INDEX_GOAL_REVENUE => "ga:goal{$gaIdGoal}Value",

            // nb_visits_converted is calculated properly in createGoalSpecificMetricArray
            Metrics::INDEX_GOAL_NB_VISITS_CONVERTED => "ga:goal{$gaIdGoal}ConversionRate",
        ];
    }

    private function gaBatchGet($date, $metricNames, $options)
    {
        $dimensions = [];
        foreach ($options['dimensions'] as $gaDimension) {
            $dimensions[] = $this->makeGaDimension($gaDimension);
        }

        $segments = [];
        if (!empty($options['segment'])) {
            $segments[] = $this->makeGaSegment($options['segment']);
            $dimensions[] = $this->makeGaSegmentDimension();
        }

        $metricNames = array_values($metricNames);
        $metrics = array_map(function ($name) { return $this->makeGaMetric($name); }, $metricNames);

        $request = new \Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($this->viewId);
        $request->setDateRanges([$this->makeGaDateRange($date)]);
        $request->setDimensions($dimensions);
        $request->setSegments($segments);
        $request->setMetrics($metrics);

        if (isset($options['orderBys'])) {
            $request->setOrderBys($this->makeGaOrderBys($options['orderBys']));
        }

        $getReport = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        $getReport->setReportRequests([$request]);

        $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests([$request]);

        $attempts = 0;
        while ($attempts < self::MAX_ATTEMPTS) {
            try {
                return $this->gaService->reports->batchGet($body);
            } catch (\Exception $ex) {
                if ($ex->getCode() != 403 && $ex->getCode() != 429) {
                    throw $ex;
                }

                ++$attempts;
                sleep(1);
            }
        }
    }

    private function makeGaSegment($segment)
    {
        $segmentObj = new \Google_Service_AnalyticsReporting_Segment();
        if (isset($segment['segmentId'])) {
            $segmentObj->setSegmentId($segment['segmentId']);
        } else {
            $segmentObj->setDynamicSegment($segment['dynamicSegment']);
        }
        return $segmentObj;
    }

    private function makeGaDateRange($date)
    {
        $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($date);
        $dateRange->setEndDate($date);
        return $dateRange;
    }

    private function makeGaSegmentDimension()
    {
        $segmentDimensions = new \Google_Service_AnalyticsReporting_Dimension();
        $segmentDimensions->setName("ga:segment");
        return $segmentDimensions;
    }

    private function makeGaDimension($gaDimension)
    {
        $result = new \Google_Service_AnalyticsReporting_Dimension();
        $result->setName($gaDimension);
        return $result;
    }

    private function makeGaMetric($gaMetric)
    {
        $metric = new \Google_Service_AnalyticsReporting_Metric();
        $metric->setExpression($gaMetric);
        return $metric;
    }

    private static function getQuotientFromPercentage($percentage)
    {
        if ($percentage === false) {
            return 0;
        }

        $quotient = trim($percentage);
        $quotient = rtrim($quotient, '%');
        $quotient = (float) $quotient;
        $quotient = $quotient / 100;
        return $quotient;
    }

    private function makeGaOrderBys($orderBys)
    {
        $gaOrderBys = [];
        foreach ($orderBys as $orderByInfo) {
            $orderBy = new \Google_Service_AnalyticsReporting_OrderBy();
            $orderBy->setFieldName($orderByInfo['field']);
            $orderBy->setOrderType('VALUE');
            $orderBy->setSortOrder(strtoupper($orderByInfo['order']));
        }
        return $gaOrderBys;
    }
}
