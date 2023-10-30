<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Unit;

use Piwik\Common;
use Piwik\Date;
use Piwik\Plugins\GoogleAnalyticsImporter\GoogleAnalyticsImporter;
/**
 * @group GoogleAnalyticsImporter
 * @group NotificationDisplayTest
 * @group Plugins
 */
class NotificationDisplayTest extends \PHPUnit\Framework\TestCase
{
    public function setUp() : void
    {
        // set up here if needed
    }
    public function tearDown() : void
    {
        // tear down here if needed
    }
    /**
     * Check if the dates overlap
     */
    public function testSimpleAddition()
    {
        $importStart = Date::factory('2022-11-01');
        $importEnd = Date::factory('2022-11-15');
        $reportStart = Date::factory('2022-10-01');
        $reportEnd = Date::factory('2022-11-20');
        $period = [['start_time' => $importStart, 'end_time' => $importEnd], ['start_time' => $reportStart, 'end_time' => $reportEnd]];
        $this->assertEquals(\true, GoogleAnalyticsImporter::datesOverlap($period));
        $reportEnd = Date::factory('2022-10-15');
        $period[1]['end_time'] = $reportEnd;
        $this->assertEquals(\false, GoogleAnalyticsImporter::datesOverlap($period));
        $reportEnd = Date::factory('2022-11-02');
        $period[1]['end_time'] = $reportEnd;
        $this->assertEquals(\true, GoogleAnalyticsImporter::datesOverlap($period));
        $reportEnd = Date::factory('2022-11-01');
        $period[1]['end_time'] = $reportEnd;
        $this->assertEquals(\true, GoogleAnalyticsImporter::datesOverlap($period));
        $reportStart = Date::factory('2022-11-01');
        $reportEnd = Date::factory('2022-11-15');
        $period[1]['start_time'] = $reportStart;
        $period[1]['end_time'] = $reportEnd;
        $this->assertEquals(\true, GoogleAnalyticsImporter::datesOverlap($period));
        $reportStart = Date::factory('2022-11-02');
        $reportEnd = Date::factory('2022-11-16');
        $period[1]['start_time'] = $reportStart;
        $period[1]['end_time'] = $reportEnd;
        $this->assertEquals(\true, GoogleAnalyticsImporter::datesOverlap($period));
        $reportStart = Date::factory('2022-10-15');
        $reportEnd = Date::factory('2022-11-10');
        $period[1]['start_time'] = $reportStart;
        $period[1]['end_time'] = $reportEnd;
        $this->assertEquals(\true, GoogleAnalyticsImporter::datesOverlap($period));
    }
}
