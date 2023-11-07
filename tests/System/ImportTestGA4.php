<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\System;

use Piwik\Plugins\GoogleAnalyticsImporter\tests\Fixtures\ImportedFromGoogleGA4;
use Piwik\Tests\Framework\TestCase\SystemTestCase;
use Piwik\Version;
// TODO: in-table segments should be disabled for these imported reports (would need to mark records as imported and delete segment metadata in hooks)
/**
 * @group GoogleAnalyticsImporter
 * @group GoogleAnalyticsImporter_System
 */
class ImportTestGA4 extends SystemTestCase
{
    private static $CONVERSION_AWARE_VISIT_METRICS = ['nb_visits', 'nb_actions', 'sum_visit_length', 'bounce_count', 'nb_visits_converted', 'nb_conversions', 'revenue', 'goals', 'sum_daily_nb_uniq_visitors'];
    private static $ACTION_METRICS = ['nb_visits', 'nb_hits', 'entry_nb_actions', 'entry_sum_visit_length', 'entry_bounce_count', 'bounce_rate', 'sum_daily_nb_uniq_visitors'];
    private static $VISIT_TIME_METRICS = ['nb_visits', 'nb_uniq_visitors', 'nb_visits_converted', 'nb_users', 'nb_actions', 'sum_visit_length', 'bounce_count'];
    private static $ECOMMERCE_ITEM_METRICS = ['revenue', 'quantity', 'orders', 'nb_visits', 'nb_actions', 'avg_price', 'avg_quantity', 'conversion_rate'];
    private static $SITESEARCH_METRICS = ['nb_visits', 'nb_hits', 'sum_daily_nb_uniq_visitors', 'nb_pages_per_search', 'bounce_rate', 'exit_rate'];
    /**
     * @var ImportedFromGoogleGA4
     */
    public static $fixture;
    /**
     * @dataProvider getApiTestsToRun
     */
    public function testApi($api, $params)
    {
        $this->runApiTests($api, $params);
    }
    public function getApiTestsToRun()
    {
        $apiToTest = [];
        $apiNotToTest = [];
        $config = (require PIWIK_INCLUDE_PATH . '/plugins/GoogleAnalyticsImporter/config/config.php');
        $recordImporterClasses = $config['GoogleAnalyticsGA4Importer.recordImporters'];
        foreach ($recordImporterClasses as $class) {
            if ($class::PLUGIN_NAME == 'MarketingCampaignsReporting') {
                continue;
            }
            $apiToTest[] = $class::PLUGIN_NAME;
        }
        if (version_compare(Version::VERSION, '4.6.0', '<')) {
            $apiNotToTest[] = 'DevicesDetection.getBrowserEngines';
        }
        return [
            [$apiToTest, ['idSite' => self::$fixture->idSite, 'date' => self::$fixture->dateTime, 'periods' => ['day', 'week', 'month', 'year'], 'apiNotToCall' => $apiNotToTest, 'testSuffix' => '_GA4']],
            [['Goals.getDaysToConversion', 'Goals.getVisitsUntilConversion'], ['idSite' => self::$fixture->idSite, 'date' => self::$fixture->dateTime, 'periods' => ['day', 'week', 'month', 'year'], 'idGoal' => 'ecommerceOrder', 'testSuffix' => '_GA4_ecommerceOrder']],
            // custom dimensions
            ['CustomDimensions.getCustomDimension', ['idSite' => self::$fixture->idSite, 'date' => self::$fixture->dateTime, 'periods' => ['day', 'week', 'month', 'year'], 'testSuffix' => '_GA4_action', 'otherRequestParameters' => ['idDimension' => '6']]],
            ['CustomDimensions.getCustomDimension', ['idSite' => self::$fixture->idSite, 'date' => self::$fixture->dateTime, 'periods' => ['day', 'week', 'month', 'year'], 'testSuffix' => '_GA4_visit', 'otherRequestParameters' => ['idDimension' => '4']]],
            ['CustomDimensions.getCustomDimension', ['idSite' => self::$fixture->idSite, 'date' => self::$fixture->dateTime, 'periods' => ['day', 'week', 'month', 'year'], 'testSuffix' => '_GA4_extraCustomDim', 'otherRequestParameters' => ['idDimension' => '7']]],
            // flattened
            [['Referrers.getSearchEngines'], ['idSite' => self::$fixture->idSite, 'date' => self::$fixture->dateTime, 'periods' => ['day'], 'testSuffix' => '_GA4_flat', 'otherRequestParameters' => ['flat' => '1']]],
            ['MarketingCampaignsReporting', ['idSite' => self::$fixture->campaignIdSite, 'date' => self::$fixture->campaignDataDateTime, 'periods' => ['day', 'week', 'month', 'year'], 'testSuffix' => '_GA4']],
            // test aggregated w/ real visit
            ['VisitsSummary.get', ['idSite' => self::$fixture->idSite, 'date' => '2019-07-03', 'periods' => 'week', 'testSuffix' => '_GA4_aggregatedWithTrackedVisit']],
        ];
    }
    /**
     * @dataProvider getTestDataForTestApiColumns
     */
    public function testApiColumns($method, $columns)
    {
        $this->markTestSkipped("skipping for now, this test never really worked anyway");
        $expectedApiColumns = self::getExpectedApiColumns();
        if (!isset($expectedApiColumns[$method])) {
            throw new \Exception("No expected columns for {$method}");
        }
        $expectedColumns = $expectedApiColumns[$method];
        $expectedColumns = array_values($expectedColumns);
        $columns = array_values($columns);
        sort($expectedColumns);
        sort($columns);
        $this->assertEquals($expectedColumns, $columns);
    }
    public static function getOutputPrefix()
    {
        return '';
    }
    public static function getPathToTestDirectory()
    {
        return dirname(__FILE__);
    }
    public function getTestDataForTestApiColumns()
    {
        $tests = [];
        $checkedApiMethods = [];
        $expectedPath = PIWIK_INCLUDE_PATH . '/plugins/GoogleAnalyticsImporter/tests/System/expected';
        $contents = scandir($expectedPath);
        foreach ($contents as $filename) {
            if (!preg_match('/([^_]+)_year.xml$/', $filename, $matches)) {
                continue;
            }
            $method = $matches[1];
            if (!empty($checkedApiMethods[$method])) {
                continue;
            }
            if (preg_match('/^VisitorInterest\\./', $method)) {
                continue;
            }
            $importedPath = $expectedPath . '/' . $filename;
            $columns = $this->getColumnsFromXml($importedPath);
            if (empty($columns)) {
                continue;
            }
            $tests[] = [$method, $columns];
        }
        return $tests;
    }
    private function getColumnsFromXml($importedPath)
    {
        $contents = file_get_contents($importedPath);
        $element = new \SimpleXMLElement($contents);
        if (empty($element->row) || empty($element->row[0])) {
            return null;
        }
        $tagNames = [];
        for ($j = 0; $j != $element->children()->count(); ++$j) {
            $row = $element->row[$j];
            $children = $row->children();
            for ($i = 0; $i != $children->count(); ++$i) {
                $tagName = $children[$i]->getName();
                if ($tagName == 'segment' || $tagName == 'subtable' || $tagName == 'label') {
                    continue;
                }
                $tagNames[] = $tagName;
            }
        }
        return array_unique($tagNames);
    }
    private static function getExpectedApiColumns()
    {
        return ['Referrers.getWebsites' => self::$CONVERSION_AWARE_VISIT_METRICS, 'Referrers.getReferrerType' => self::$CONVERSION_AWARE_VISIT_METRICS, 'Referrers.getAll' => array_merge(self::$CONVERSION_AWARE_VISIT_METRICS, ['referer_type', 'logo', 'url']), 'Referrers.getKeywords' => self::$CONVERSION_AWARE_VISIT_METRICS, 'Referrers.getKeywordsForPageUrl' => [], 'Referrers.getKeywordsForPageTitle' => [], 'Referrers.getSearchEnginesFromKeywordId' => self::$CONVERSION_AWARE_VISIT_METRICS, 'Referrers.getSearchEngines' => array_merge(self::$CONVERSION_AWARE_VISIT_METRICS, ['url', 'logo']), 'Referrers.getCampaigns' => self::$CONVERSION_AWARE_VISIT_METRICS, 'Referrers.getKeywordsFromCampaignId' => self::$CONVERSION_AWARE_VISIT_METRICS, 'Referrers.getUrlsFromWebsiteId' => self::$CONVERSION_AWARE_VISIT_METRICS, 'Referrers.getSocials' => array_merge(self::$CONVERSION_AWARE_VISIT_METRICS, ['url', 'logo']), 'Referrers.getUrlsForSocial' => array_merge(self::$CONVERSION_AWARE_VISIT_METRICS, ['url']), 'Actions.getPageTitles' => self::$ACTION_METRICS, 'Actions.getPageUrls' => array_merge(self::$ACTION_METRICS, ['url']), 'Actions.getExitPageTitles' => self::$ACTION_METRICS, 'Actions.getExitPageUrls' => array_merge(self::$ACTION_METRICS, ['url']), 'Actions.getEntryPageTitles' => self::$ACTION_METRICS, 'Actions.getEntryPageUrls' => array_merge(self::$ACTION_METRICS, ['url']), 'Actions.getSiteSearchKeywords' => self::$SITESEARCH_METRICS, 'Actions.getSiteSearchCategories' => ['nb_visits', 'nb_actions', 'sum_visit_length', 'bounce_count', 'nb_visits_converted', 'nb_conversions', 'revenue', 'nb_hits', 'sum_daily_nb_uniq_visitors', 'nb_pages_per_search'], 'VisitTime.getByDayOfWeek' => array_merge(self::$VISIT_TIME_METRICS, ['day_of_week']), 'UserLanguage.getLanguage' => self::$CONVERSION_AWARE_VISIT_METRICS, 'UserLanguage.getLanguageCode' => self::$CONVERSION_AWARE_VISIT_METRICS, 'UserCountry.getContinent' => array_merge(self::$CONVERSION_AWARE_VISIT_METRICS, ['code']), 'UserCountry.getRegion' => array_merge(self::$CONVERSION_AWARE_VISIT_METRICS, ['country', 'country_name', 'region', 'region_name', 'logo']), 'UserCountry.getCountry' => array_merge(self::$CONVERSION_AWARE_VISIT_METRICS, ['code', 'logo', 'logoHeight']), 'UserCountry.getCity' => array_merge(self::$CONVERSION_AWARE_VISIT_METRICS, ['city', 'city_name', 'country', 'country_name', 'region', 'region_name', 'logo', 'lat', 'long']), 'Resolution.getResolution' => self::$CONVERSION_AWARE_VISIT_METRICS, 'Resolution.getConfiguration' => self::$CONVERSION_AWARE_VISIT_METRICS, 'Goals.getItemsSku' => self::$ECOMMERCE_ITEM_METRICS, 'Goals.getItemsName' => self::$ECOMMERCE_ITEM_METRICS, 'Goals.getItemsCategory' => self::$ECOMMERCE_ITEM_METRICS, 'Events.getName' => array_merge(self::$CONVERSION_AWARE_VISIT_METRICS, ['avg_event_value']), 'Events.getCategory' => array_merge(self::$CONVERSION_AWARE_VISIT_METRICS, ['avg_event_value']), 'Events.getAction' => array_merge(self::$CONVERSION_AWARE_VISIT_METRICS, ['avg_event_value']), 'VisitTime.getVisitInformationPerLocalTime' => self::$CONVERSION_AWARE_VISIT_METRICS, 'DevicesDetection.getType' => array_merge(self::$CONVERSION_AWARE_VISIT_METRICS, ['logo']), 'DevicesDetection.getOsVersions' => array_merge(self::$CONVERSION_AWARE_VISIT_METRICS, ['logo']), 'DevicesDetection.getOsFamilies' => array_merge(self::$CONVERSION_AWARE_VISIT_METRICS, ['logo']), 'DevicesDetection.getModel' => self::$CONVERSION_AWARE_VISIT_METRICS, 'DevicesDetection.getBrowsers' => array_merge(self::$CONVERSION_AWARE_VISIT_METRICS, ['logo']), 'DevicesDetection.getBrowserVersions' => array_merge(self::$CONVERSION_AWARE_VISIT_METRICS, ['logo']), 'DevicesDetection.getBrowserFamilies' => array_merge(self::$CONVERSION_AWARE_VISIT_METRICS, ['logo']), 'DevicesDetection.getBrand' => array_merge(self::$CONVERSION_AWARE_VISIT_METRICS, ['logo'])];
    }
}
\Piwik\Plugins\GoogleAnalyticsImporter\tests\System\ImportTestGA4::$fixture = new ImportedFromGoogleGA4();
