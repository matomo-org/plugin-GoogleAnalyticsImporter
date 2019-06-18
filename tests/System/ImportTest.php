<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\System;

use Piwik\Plugins\GoogleAnalyticsImporter\tests\Fixtures\ImportedFromGoogle;
use Piwik\Tests\Framework\TestCase\SystemTestCase;

// TODO: segments should be disabled for these imported reports (would need to mark records as imported and delete segment metadata in hooks)
class ImportTest extends SystemTestCase
{
    private static $CONVERSION_AWARE_VISIT_METRICS = [
        'nb_uniq_visitors',
        'nb_visits',
        'nb_actions',
        'sum_visit_length',
        'bounce_count',
        'nb_visits_converted',
        'nb_conversions',
        'revenue',
        'goals',
    ];

    private static $ACTION_METRICS = [
        'nb_visits',
        'nb_uniq_visitors',
        'nb_hits',
        'sum_time_spent',
        'entry_nb_uniq_visitors',
        'entry_nb_visits',
        'entry_nb_actions',
        'entry_sum_visit_length',
        'entry_bounce_count',
        'exit_nb_uniq_visitors',
        'exit_nb_visits',
        'avg_bandwidth',
        'avg_time_on_page',
        'bounce_rate',
        'exit_rate',
    ];

    /**
     * @var ImportedFromGoogle
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
        $apiToTest = [
            'Referrers',
            'Actions',
            'CustomDimensions',
            'CustomVariables',
            'Goals',
            'DevicesDetection',
            'Events',
        ];

        return [
            [$apiToTest, [
                'idSite' => self::$fixture->idSite,
                'date' => '2018-12-03',
                'periods' => ['day', 'week', 'month', 'year'],
            ]],
        ];
    }

    /**
     * @dependsOn testApi
     * @dataProvider getTestDataForTestApiColumns
     */
    public function testApiColumns($method, $columns)
    {
        $expectedApiColumns = self::getExpectedApiColumns();
        if (!isset($expectedApiColumns[$method])) {
            throw new \Exception("No expected columns for $method");
        }

        $this->assertEquals($expectedApiColumns[$method], $columns);
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
            if (!preg_match('/([^_]+)_day.xml$/', $filename, $matches)) {
                continue;
            }

            $method = $matches[1];
            if (!empty($checkedApiMethods[$method])) {
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

        if (empty($element->row)
            || empty($element->row[0])
        ) {
            return null;
        }

        $row = $element->row[0];
        $children = $row->children();

        $tagNames = [];
        for ($i = 0; $i != $children->count(); ++$i) {
            $tagName = $children[$i]->getName();
            if ($tagName == 'segment' || $tagName == 'subtable' || $tagName == 'label') {
                continue;
            }
            $tagNames[] = $tagName;
        }
        return $tagNames;
    }

    private static function getExpectedApiColumns()
    {
        return [
            'Referrers.getWebsites' => self::$CONVERSION_AWARE_VISIT_METRICS,
            'Referrers.getReferrerType' => self::$CONVERSION_AWARE_VISIT_METRICS,
            'Referrers.getAll' => array_merge(self::$CONVERSION_AWARE_VISIT_METRICS, ['referer_type']),
            'Referrers.getKeywords' => self::$CONVERSION_AWARE_VISIT_METRICS,
            'Referrers.getKeywordsForPageUrl' => [],
            'Referrers.getKeywordsForPageTitle' => [],
            'Referrers.getSearchEnginesFromKeywordId' => self::$CONVERSION_AWARE_VISIT_METRICS,
            'Referrers.getSearchEngines' => array_merge(self::$CONVERSION_AWARE_VISIT_METRICS, ['url', 'logo']),
            'Referrers.getCampaigns' => self::$CONVERSION_AWARE_VISIT_METRICS,
            'Referrers.getKeywordsFromCampaignId' => self::$CONVERSION_AWARE_VISIT_METRICS,
            'Referrers.getUrlsFromWebsiteId' => self::$CONVERSION_AWARE_VISIT_METRICS,
            'Referrers.getSocials' => self::$CONVERSION_AWARE_VISIT_METRICS,
            'Referrers.getUrlsForSocial' => self::$CONVERSION_AWARE_VISIT_METRICS,

            'Actions.getPageTitles' => self::$ACTION_METRICS,
            'Actions.getPageUrls' => [
                'nb_visits',
                'nb_hits',
                'sum_time_spent',
                'exit_nb_visits',
                'entry_nb_visits',
                'entry_nb_actions',
                'entry_sum_visit_length',
                'entry_bounce_count',
                'avg_bandwidth',
                'avg_time_on_page',
                'bounce_rate',
                'exit_rate',
            ],
        ];
    }
}

ImportTest::$fixture = new ImportedFromGoogle();