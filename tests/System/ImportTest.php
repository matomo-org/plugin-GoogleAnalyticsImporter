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
        $apiToTest = 'all';

        return [
            [$apiToTest, [
                'idSite' => self::$fixture->idSite,
                'date' => '2018-12-01',
                'periods' => ['day', 'week', 'month', 'year'],
            ]],
        ];
    }
}

ImportTest::$fixture = new ImportedFromGoogle();