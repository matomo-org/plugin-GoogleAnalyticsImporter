<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Unit\Google;

use Piwik\Metrics;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleMetricMapper;
use Piwik\Plugins\GoogleAnalyticsImporter\Input\EndDate;

class EndDateTest extends \PHPUnit_Framework_TestCase
{

    public function test_getMaxEndDate()
    {
    	$maxEndDate = new EndDate();
    	$this->assertNull($maxEndDate->getMaxEndDate());
    }

    public function test_getMaxEndDate_forcedDate()
    {
    	$maxEndDate = new EndDate();
    	$maxEndDate->forceMaxEndDate = '2020-03-19';
    	$this->assertSame('2020-03-19', $maxEndDate->getMaxEndDate()->toString());
    }

    public function test_limitMaxEndDateIfNeeded()
    {
    	$maxEndDate = new EndDate();
    	$this->assertSame('', $maxEndDate->limitMaxEndDateIfNeeded(''));
    	$this->assertSame('2019-01-02', $maxEndDate->limitMaxEndDateIfNeeded('2019-01-02'));
    }

    public function test_limitMaxEndDateIfNeeded_noNeedToLimit()
    {
    	$maxEndDate = new EndDate();
	    $maxEndDate->forceMaxEndDate = '2020-03-19';
    	$this->assertSame('2019-03-19', $maxEndDate->limitMaxEndDateIfNeeded('2019-01-02'));
    }

    public function test_limitMaxEndDateIfNeeded_whenNoEndDateSetShouldLimit()
    {
    	$maxEndDate = new EndDate();
	    $maxEndDate->forceMaxEndDate = '2020-03-19';
    	$this->assertSame('2019-03-19', $maxEndDate->limitMaxEndDateIfNeeded(''));
    }

    public function test_limitMaxEndDateIfNeeded_shouldLimitWhenMaxDateOlder()
    {
    	$maxEndDate = new EndDate();
	    $maxEndDate->forceMaxEndDate = '2018-03-19';
    	$this->assertSame('2018-03-19', $maxEndDate->limitMaxEndDateIfNeeded('2019-01-02'));
    }

}