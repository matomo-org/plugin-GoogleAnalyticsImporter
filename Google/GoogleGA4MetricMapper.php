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
class GoogleGA4MetricMapper
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
        $goalSpecificMetrics[] = 'sessions';
        // for nb_visits_converted
        return [
            // visit metrics
            Metrics::INDEX_NB_UNIQ_VISITORS => 'totalUsers',
            Metrics::INDEX_NB_VISITS => 'sessions',
            Metrics::INDEX_NB_ACTIONS => 'eventCount',
            Metrics::INDEX_SUM_VISIT_LENGTH => ['metric' => 'userEngagementDuration', 'calculate' => function (Row $row) {
                return floor($row->getColumn('userEngagementDuration'));
            }],
            Metrics::INDEX_BOUNCE_COUNT => ['metric' => ['sessions', 'bounceRate'], 'calculate' => function (Row $row) {
                return $row->getColumn('sessions') * $row->getColumn('bounceRate');
            }],
            // TODO: goalConversionRateAll doesn't seem to include ecommerce orders. not sure how to make it accurate in this case...
            Metrics::INDEX_NB_VISITS_CONVERTED => 'conversions',
            // conversion aware
            Metrics::INDEX_NB_CONVERSIONS => ['metric' => ['conversions', 'transactions'], 'calculate' => function (Row $row) {
                return $row->getColumn('conversions') + $row->getColumn('transactions');
            }],
            Metrics::INDEX_REVENUE => 'totalRevenue',
            // goal specific
            Metrics::INDEX_GOALS => ['metric' => $goalSpecificMetrics, 'calculate' => function ($metrics) {
                return $this->createGoalSpecificMetricArray($metrics);
            }],
            // actions
            Metrics::INDEX_PAGE_NB_HITS => 'screenPageViews',
            Metrics::INDEX_PAGE_SUM_TIME_SPENT => ['metric' => 'userEngagementDuration', 'calculate' => function (Row $row) {
                return round($row->getColumn('userEngagementDuration'));
            }],
            // events
            Metrics::INDEX_EVENT_NB_HITS => 'eventCount',
            Metrics::INDEX_EVENT_SUM_EVENT_VALUE => 'eventValue',
            // actions (requires correct dimension)
            Metrics::INDEX_PAGE_EXIT_NB_UNIQ_VISITORS => 'totalUsers',
            //            Metrics::INDEX_PAGE_EXIT_NB_VISITS => 'ga:exits', Not available in GA4
            // actions (requires correct dimension)
            Metrics::INDEX_PAGE_ENTRY_NB_UNIQ_VISITORS => 'totalUsers',
            //            Metrics::INDEX_PAGE_ENTRY_NB_VISITS => 'ga:entrances', Not available in GA4
            Metrics::INDEX_PAGE_ENTRY_NB_ACTIONS => 'eventCount',
            Metrics::INDEX_PAGE_ENTRY_SUM_VISIT_LENGTH => ['metric' => 'userEngagementDuration', 'calculate' => function (Row $row) {
                return floor($row->getColumn('userEngagementDuration'));
            }],
            Metrics::INDEX_PAGE_ENTRY_BOUNCE_COUNT => ['metric' => ['sessions', 'bounceRate'], 'calculate' => function (Row $row) {
                return $row->getColumn('sessions') * $row->getColumn('bounceRate');
            }],
            // actions (requires correct dimensions)
            Metrics::INDEX_PAGE_IS_FOLLOWING_SITE_SEARCH_NB_HITS => 'eventCount',
            //            Metrics::INDEX_PAGE_SUM_TIME_GENERATION => 'ga:pageDownloadTime', Not available in GA4
            //            Metrics::INDEX_PAGE_NB_HITS_WITH_TIME_GENERATION => 'ga:pageLoadSample', Not available in GA4
            // ecommerce item metrics (requires correct dimensions)
            //            Metrics::INDEX_ECOMMERCE_ITEM_REVENUE => 'itemRevenue',
            //            Metrics::INDEX_ECOMMERCE_ITEM_QUANTITY => 'itemsPurchased',
            Metrics::INDEX_ECOMMERCE_ITEM_PRICE => 'averagePurchaseRevenue',
            Metrics::INDEX_ECOMMERCE_ORDERS => 'ecommercePurchases',
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
        return [
            //            Metrics::INDEX_GOAL_ECOMMERCE_REVENUE_SUBTOTAL => 'itemRevenue',
            Metrics::INDEX_GOAL_ECOMMERCE_REVENUE_TAX => 'taxAmount',
            Metrics::INDEX_GOAL_ECOMMERCE_REVENUE_SHIPPING => 'shippingAmount',
        ];
    }
    public function getGoalSpecificMetricIndicesToGametrics($gaIdGoal)
    {
        return [];
        //Not available in GA4
        return [
            Metrics::INDEX_GOAL_NB_CONVERSIONS => "ga:goal{$gaIdGoal}Completions",
            Metrics::INDEX_GOAL_REVENUE => "ga:goal{$gaIdGoal}Value",
            // nb_visits_converted is calculated properly in createGoalSpecificMetricArray
            Metrics::INDEX_GOAL_NB_VISITS_CONVERTED => "ga:goal{$gaIdGoal}ConversionRate",
        ];
    }
    public function getEcommerceGoalSpecificMetrics()
    {
        return [Metrics::INDEX_GOAL_NB_CONVERSIONS => 'transactions'];
    }
    private static function calculateConvertedVisits(Row $row, $gaName)
    {
        return floor(self::getQuotientFromPercentage($row->getColumn($gaName)) * $row->getColumn('sessions'));
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
