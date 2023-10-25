<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Unit\Google;

use PHPUnit\Framework\TestCase;
use Piwik\Metrics;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleMetricMapper;
/**
 * @group GoogleAnalyticsImporter
 * @group GoogleAnalyticsImporter_Unit
 */
class GoogleMetricMapperTest extends TestCase
{
    public function test_constructor_shouldInitializeAllCorrectMappings()
    {
        $metricMapper = new GoogleMetricMapper($isEcommerceEnabled = \false, $goalsMapping = []);
        $mappings = $metricMapper->getMappings();
        $this->assertEquals([Metrics::INDEX_NB_UNIQ_VISITORS, Metrics::INDEX_NB_VISITS, Metrics::INDEX_NB_ACTIONS, Metrics::INDEX_SUM_VISIT_LENGTH, Metrics::INDEX_BOUNCE_COUNT, Metrics::INDEX_NB_VISITS_CONVERTED, Metrics::INDEX_NB_CONVERSIONS, Metrics::INDEX_REVENUE, Metrics::INDEX_GOALS, Metrics::INDEX_PAGE_NB_HITS, Metrics::INDEX_PAGE_SUM_TIME_SPENT, Metrics::INDEX_EVENT_NB_HITS, Metrics::INDEX_EVENT_SUM_EVENT_VALUE, Metrics::INDEX_PAGE_EXIT_NB_UNIQ_VISITORS, Metrics::INDEX_PAGE_EXIT_NB_VISITS, Metrics::INDEX_PAGE_ENTRY_NB_UNIQ_VISITORS, Metrics::INDEX_PAGE_ENTRY_NB_VISITS, Metrics::INDEX_PAGE_ENTRY_NB_ACTIONS, Metrics::INDEX_PAGE_ENTRY_SUM_VISIT_LENGTH, Metrics::INDEX_PAGE_ENTRY_BOUNCE_COUNT, Metrics::INDEX_PAGE_IS_FOLLOWING_SITE_SEARCH_NB_HITS, Metrics::INDEX_PAGE_SUM_TIME_GENERATION, Metrics::INDEX_PAGE_NB_HITS_WITH_TIME_GENERATION, Metrics::INDEX_ECOMMERCE_ITEM_REVENUE, Metrics::INDEX_ECOMMERCE_ITEM_QUANTITY, Metrics::INDEX_ECOMMERCE_ITEM_PRICE, Metrics::INDEX_ECOMMERCE_ORDERS], array_keys($mappings));
        $this->assertEquals(['ga:sessions'], $mappings[Metrics::INDEX_GOALS]['metric']);
    }
    public function test_constructor_shouldInitializeAllCorrectMappings_whenEcommerceAndGoalsAreEnabled()
    {
        $metricMapper = new GoogleMetricMapper($isEcommerceEnabled = \true, $goalsMapping = [1 => 3, 2 => 4]);
        $mappings = $metricMapper->getMappings();
        $this->assertEquals([Metrics::INDEX_NB_UNIQ_VISITORS, Metrics::INDEX_NB_VISITS, Metrics::INDEX_NB_ACTIONS, Metrics::INDEX_SUM_VISIT_LENGTH, Metrics::INDEX_BOUNCE_COUNT, Metrics::INDEX_NB_VISITS_CONVERTED, Metrics::INDEX_NB_CONVERSIONS, Metrics::INDEX_REVENUE, Metrics::INDEX_GOALS, Metrics::INDEX_PAGE_NB_HITS, Metrics::INDEX_PAGE_SUM_TIME_SPENT, Metrics::INDEX_EVENT_NB_HITS, Metrics::INDEX_EVENT_SUM_EVENT_VALUE, Metrics::INDEX_PAGE_EXIT_NB_UNIQ_VISITORS, Metrics::INDEX_PAGE_EXIT_NB_VISITS, Metrics::INDEX_PAGE_ENTRY_NB_UNIQ_VISITORS, Metrics::INDEX_PAGE_ENTRY_NB_VISITS, Metrics::INDEX_PAGE_ENTRY_NB_ACTIONS, Metrics::INDEX_PAGE_ENTRY_SUM_VISIT_LENGTH, Metrics::INDEX_PAGE_ENTRY_BOUNCE_COUNT, Metrics::INDEX_PAGE_IS_FOLLOWING_SITE_SEARCH_NB_HITS, Metrics::INDEX_PAGE_SUM_TIME_GENERATION, Metrics::INDEX_PAGE_NB_HITS_WITH_TIME_GENERATION, Metrics::INDEX_ECOMMERCE_ITEM_REVENUE, Metrics::INDEX_ECOMMERCE_ITEM_QUANTITY, Metrics::INDEX_ECOMMERCE_ITEM_PRICE, Metrics::INDEX_ECOMMERCE_ORDERS], array_keys($mappings));
        $this->assertEquals(['ga:goal3Completions', 'ga:goal3Value', 'ga:goal3ConversionRate', 'ga:goal4Completions', 'ga:goal4Value', 'ga:goal4ConversionRate', 'ga:transactions', 'ga:transactionRevenue', 'ga:itemQuantity', 'ga:sessions'], $mappings[Metrics::INDEX_GOALS]['metric']);
    }
    public function test_getMappedMetrics_shouldReturnMappedMetricsForMetricIndices()
    {
        $metricMapper = new GoogleMetricMapper($isEcommerceEnabled = \false, $goalsMapping = [1 => 3, 2 => 4]);
        $metrics = $metricMapper->getMappedMetrics([Metrics::INDEX_NB_UNIQ_VISITORS, Metrics::INDEX_SUM_VISIT_LENGTH, Metrics::INDEX_NB_VISITS_CONVERTED, Metrics::INDEX_GOALS]);
        $this->assertEquals(['ga:users', 'ga:sessionDuration', 'ga:goalConversionRateAll', 'ga:sessions', 'ga:goal3Completions', 'ga:goal3Value', 'ga:goal3ConversionRate', 'ga:goal4Completions', 'ga:goal4Value', 'ga:goal4ConversionRate'], $metrics);
    }
    public function test_getMappedMetrics_usesCustomMetricMappingsIfSet()
    {
        $metricMapper = new GoogleMetricMapper($isEcommerceEnabled = \true, $goalsMapping = []);
        $metricMapper->setCustomMappings([Metrics::INDEX_NB_UNIQ_VISITORS => 'ga:someMetric', Metrics::INDEX_SUM_VISIT_LENGTH => 'ga:someOtherMetric']);
        $metrics = $metricMapper->getMappedMetrics([Metrics::INDEX_NB_UNIQ_VISITORS, Metrics::INDEX_SUM_VISIT_LENGTH, Metrics::INDEX_NB_VISITS_CONVERTED, Metrics::INDEX_GOALS]);
        $this->assertEquals(['ga:someMetric', 'ga:someOtherMetric', 'ga:goalConversionRateAll', 'ga:sessions', 'ga:transactions', 'ga:transactionRevenue', 'ga:itemQuantity'], $metrics);
    }
}
