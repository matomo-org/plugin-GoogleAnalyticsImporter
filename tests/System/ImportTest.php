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
        $apiToTest = 'Referrers.getWebsites'; // TODO: change to 'all'

        return [
            [$apiToTest, [
                'idSite' => self::$fixture->idSite,
                'date' => '2018-12-01',
                'periods' => ['day', 'week', 'month', 'year'],
            ]],
        ];
    }

    public static function getOutputPrefix()
    {
        return '';
    }

    public static function getPathToTestDirectory()
    {
        return dirname(__FILE__);
    }
}

ImportTest::$fixture = new ImportedFromGoogle();