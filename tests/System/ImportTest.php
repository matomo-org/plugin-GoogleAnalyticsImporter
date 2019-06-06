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

class ImportTest extends SystemTestCase
{
    public static $EXPECTED_API_COLUMNS = [
        'Referrers.getWebsites' => [
            'nb_uniq_visitors',
            'nb_visits',
            'nb_actions',
            'sum_visit_length',
            'bounce_count',
            'nb_visits_converted',
            'goals',
            'nb_conversions',
            'revenue',
        ],
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
        // TODO: we should also test that the columns in the output match the columns in another matomo test output. make sure we're getting all the info we can.
        $apiToTest = 'Referrers'; // TODO: change to 'all'

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
        if (empty(self::$EXPECTED_API_COLUMNS[$method])) {
            throw new \Exception("No expected columns for $method");
        }

        $this->assertEquals(self::$EXPECTED_API_COLUMNS[$method], $columns);
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
}

ImportTest::$fixture = new ImportedFromGoogle();