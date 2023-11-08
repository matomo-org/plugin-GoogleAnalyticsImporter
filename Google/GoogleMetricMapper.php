<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\Google;

use Piwik\DataTable\Row;
use Piwik\Metrics;
use Piwik\Tracker\GoalManager;
class GoogleMetricMapper
{
    /**
     * @var array
     */
    private $mappings;
    /**
     * @var array
     */
    private $goalsMapping;
    /**
     * @var int
     */
    private $isEcommerceEnabled;
    /**
     * @var array
     */
    private $customMappings = [];
    public function __construct($isEcommerceEnabled, $goalsMapping)
    {
        $this->isEcommerceEnabled = $isEcommerceEnabled;
        $this->goalsMapping = $goalsMapping;
        $this->mappings = $this->getMetricIndicesToGaMetrics();
    }
    public function setCustomMappings(array $customMappings)
    {
        $this->customMappings = $customMappings;
    }
    public function getMappings()
    {
        return $this->customMappings + $this->mappings;
    }
    public function getMappedMetrics($metrics)
    {
        $mappings = $this->getMappings();
        $mappedMetricsInfo = [];
        foreach ($metrics as $index) {
            if (!isset($mappings[$index])) {
                throw new \Exception("Don't know how to map metric index {$index} to GA metric.");
            }
            $gaMetric = $mappings[$index];
            $metric = $gaMetric;
            if (isset($gaMetric['metric'])) {
                $metric = $gaMetric['metric'];
            }
            if (!is_array($metric)) {
                $metric = [$metric];
            }
            $mappedMetricsInfo[$index] = $metric;
        }
        $gaMetricsToQuery = [];
        foreach ($mappedMetricsInfo as $metricsList) {
            foreach ($metricsList as $gaMetric) {
                $gaMetricsToQuery[] = $gaMetric;
            }
        }
        $gaMetricsToQuery = array_unique($gaMetricsToQuery);
        return $gaMetricsToQuery;
    }
    private function getMetricIndicesToGaMetrics()
    {
        $goalSpecificMetrics = [];
        foreach ($this->goalsMapping as $idGoal => $gaIdGoal) {
            $goalSpecificMetrics = array_merge($goalSpecificMetrics, array_values($this->getGoalSpecificMetricIndicesToGametrics($gaIdGoal)));
        }
        if ($this->isEcommerceEnabled) {
            $goalSpecificMetrics = array_merge($goalSpecificMetrics, array_values($this->getEcommerceGoalSpecificMetrics()));
        }
        $goalSpecificMetrics[] = 'ga:sessions';
        // for nb_visits_converted
        return [
            // visit metrics
            Metrics::INDEX_NB_UNIQ_VISITORS => 'ga:users',
            Metrics::INDEX_NB_VISITS => 'ga:sessions',
            Metrics::INDEX_NB_ACTIONS => 'ga:hits',
            Metrics::INDEX_SUM_VISIT_LENGTH => ['metric' => 'ga:sessionDuration', 'calculate' => function (Row $row) {
                return floor($row->getColumn('ga:sessionDuration'));
            }],
            Metrics::INDEX_BOUNCE_COUNT => 'ga:bounces',
            // TODO: goalConversionRateAll doesn't seem to include ecommerce orders. not sure how to make it accurate in this case...
            Metrics::INDEX_NB_VISITS_CONVERTED => ['metric' => ['ga:goalConversionRateAll', 'ga:sessions'], 'calculate' => function (Row $row) {
                return self::calculateConvertedVisits($row, 'ga:goalConversionRateAll');
            }],
            // conversion aware
            Metrics::INDEX_NB_CONVERSIONS => ['metric' => ['ga:goalCompletionsAll', 'ga:transactions'], 'calculate' => function (Row $row) {
                return $row->getColumn('ga:goalCompletionsAll') + $row->getColumn('ga:transactions');
            }],
            Metrics::INDEX_REVENUE => 'ga:totalValue',
            // goal specific
            Metrics::INDEX_GOALS => ['metric' => $goalSpecificMetrics, 'calculate' => function ($metrics) {
                return $this->createGoalSpecificMetricArray($metrics);
            }],
            // actions
            Metrics::INDEX_PAGE_NB_HITS => 'ga:pageviews',
            Metrics::INDEX_PAGE_SUM_TIME_SPENT => ['metric' => 'ga:timeOnPage', 'calculate' => function (Row $row) {
                return round($row->getColumn('ga:timeOnPage'));
            }],
            // events
            Metrics::INDEX_EVENT_NB_HITS => 'ga:totalEvents',
            Metrics::INDEX_EVENT_SUM_EVENT_VALUE => 'ga:eventValue',
            // actions (requires correct dimension)
            Metrics::INDEX_PAGE_EXIT_NB_UNIQ_VISITORS => 'ga:users',
            Metrics::INDEX_PAGE_EXIT_NB_VISITS => 'ga:exits',
            // actions (requires correct dimension)
            Metrics::INDEX_PAGE_ENTRY_NB_UNIQ_VISITORS => 'ga:users',
            Metrics::INDEX_PAGE_ENTRY_NB_VISITS => 'ga:entrances',
            Metrics::INDEX_PAGE_ENTRY_NB_ACTIONS => 'ga:hits',
            Metrics::INDEX_PAGE_ENTRY_SUM_VISIT_LENGTH => ['metric' => 'ga:sessionDuration', 'calculate' => function (Row $row) {
                return floor($row->getColumn('ga:sessionDuration'));
            }],
            Metrics::INDEX_PAGE_ENTRY_BOUNCE_COUNT => 'ga:bounces',
            // actions (requires correct dimensions)
            Metrics::INDEX_PAGE_IS_FOLLOWING_SITE_SEARCH_NB_HITS => 'ga:hits',
            Metrics::INDEX_PAGE_SUM_TIME_GENERATION => 'ga:pageDownloadTime',
            Metrics::INDEX_PAGE_NB_HITS_WITH_TIME_GENERATION => 'ga:pageLoadSample',
            // ecommerce item metrics (requires correct dimensions)
            Metrics::INDEX_ECOMMERCE_ITEM_REVENUE => 'ga:itemRevenue',
            Metrics::INDEX_ECOMMERCE_ITEM_QUANTITY => 'ga:itemQuantity',
            Metrics::INDEX_ECOMMERCE_ITEM_PRICE => 'ga:revenuePerItem',
            Metrics::INDEX_ECOMMERCE_ORDERS => 'ga:uniquePurchases',
        ];
    }
    private function createGoalSpecificMetricArray(Row $row)
    {
        $result = [];
        foreach ($this->goalsMapping as $idGoal => $gaIdGoal) {
            $goalSpecificMetrics = $this->getGoalSpecificMetricIndicesToGametrics($gaIdGoal);
            $innerColumns = [];
            foreach ($goalSpecificMetrics as $index => $gaName) {
                if ($index == Metrics::INDEX_GOAL_NB_VISITS_CONVERTED && $row->getColumn($gaName) !== \false) {
                    $value = self::calculateConvertedVisits($row, $gaName);
                } else {
                    $value = $row->getColumn($gaName);
                }
                if ($value !== \false) {
                    $innerColumns[$index] = $value;
                }
            }
            $result[$idGoal] = $innerColumns;
        }
        if ($this->isEcommerceEnabled) {
            $goalSpecificMetrics = $this->getEcommerceGoalSpecificMetrics();
            $innerColumns = [];
            foreach ($goalSpecificMetrics as $index => $gaName) {
                $value = $row->getColumn($gaName);
                if ($value !== \false) {
                    $innerColumns[$index] = $value;
                }
            }
            $result[GoalManager::IDGOAL_ORDER] = $innerColumns;
        }
        return $result;
    }
    public function getEcommerceMetricIndicesToGaMetrics()
    {
        return [Metrics::INDEX_GOAL_ECOMMERCE_REVENUE_SUBTOTAL => 'ga:transactionRevenue', Metrics::INDEX_GOAL_ECOMMERCE_REVENUE_TAX => 'ga:transactionTax', Metrics::INDEX_GOAL_ECOMMERCE_REVENUE_SHIPPING => 'ga:transactionShipping', Metrics::INDEX_GOAL_ECOMMERCE_ITEMS => 'ga:itemQuantity'];
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
    public function getEcommerceGoalSpecificMetrics()
    {
        return [Metrics::INDEX_GOAL_NB_CONVERSIONS => 'ga:transactions', Metrics::INDEX_GOAL_REVENUE => 'ga:transactionRevenue', Metrics::INDEX_GOAL_ECOMMERCE_ITEMS => 'ga:itemQuantity'];
    }
    private static function calculateConvertedVisits(Row $row, $gaName)
    {
        return floor(self::getQuotientFromPercentage($row->getColumn($gaName)) * $row->getColumn('ga:sessions'));
    }
    private static function getQuotientFromPercentage($percentage)
    {
        if ($percentage === \false) {
            return 0;
        }
        $quotient = trim($percentage);
        $quotient = rtrim($quotient, '%');
        $quotient = (float) $quotient;
        $quotient = $quotient / 100;
        return $quotient;
    }
}
