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
use Piwik\Date;
use Piwik\Plugins\GoogleAnalyticsImporter\Input\EndDate;
class EndDateTest extends TestCase
{
    public function test_getMaxEndDate_withConfigSectionButNoValue()
    {
        $mockConfig = $this->getMockConfig([]);
        $endDate = new EndDate($mockConfig);
        $this->assertNull($endDate->getMaxEndDate());
        $mockConfig = $this->getMockConfig([]);
        $endDate = new EndDate($mockConfig);
        $this->assertNull($endDate->getMaxEndDate());
    }
    public function test_getMaxEndDate_withConfigSectionWithValue()
    {
        $mockConfig = $this->getMockConfig([EndDate::CONFIG_NAME => 'today']);
        $endDate = new EndDate($mockConfig);
        $this->assertEquals(Date::factory('today'), $endDate->getMaxEndDate());
    }
    public function test_getMaxEndDate_withConfigSectionWithInvalidValue()
    {
        $mockConfig = $this->getMockConfig([EndDate::CONFIG_NAME => 'tasdlfjsadf']);
        $this->expectExceptionMessage('Invalid max end date: tasdlfjsadf');
        $endDate = new EndDate($mockConfig);
        $endDate->getMaxEndDate();
    }
    public function test_getMaxEndDate_withConfigSectionWithValue_thatIsExplicitDate()
    {
        $mockConfig = $this->getMockConfig([EndDate::CONFIG_NAME => '2023-05-06']);
        $endDate = new EndDate($mockConfig);
        $this->assertEquals(Date::factory('2023-05-06'), $endDate->getMaxEndDate());
    }
    public function test_getMaxEndDate()
    {
        $maxEndDate = new EndDate($this->getMockConfig([]));
        $this->assertNull($maxEndDate->getMaxEndDate());
    }
    public function test_getMaxEndDate_forcedDate()
    {
        $maxEndDate = new EndDate($this->getMockConfig([]));
        $maxEndDate->forceMaxEndDate = '2020-03-19';
        $this->assertSame('2020-03-19', $maxEndDate->getMaxEndDate()->toString());
    }
    public function test_limitMaxEndDateIfNeeded()
    {
        $maxEndDate = new EndDate($this->getMockConfig([]));
        $this->assertSame('', $maxEndDate->limitMaxEndDateIfNeeded(''));
        $this->assertSame('2019-01-02', $maxEndDate->limitMaxEndDateIfNeeded('2019-01-02'));
    }
    public function test_limitMaxEndDateIfNeeded_noNeedToLimit()
    {
        $maxEndDate = new EndDate($this->getMockConfig([]));
        $maxEndDate->forceMaxEndDate = '2020-03-19';
        $this->assertSame('2019-01-02', $maxEndDate->limitMaxEndDateIfNeeded('2019-01-02'));
    }
    public function test_limitMaxEndDateIfNeeded_whenNoEndDateSetShouldLimit()
    {
        $maxEndDate = new EndDate($this->getMockConfig([]));
        $maxEndDate->forceMaxEndDate = '2020-03-19';
        $this->assertSame('2020-03-19', $maxEndDate->limitMaxEndDateIfNeeded(''));
    }
    public function test_limitMaxEndDateIfNeeded_shouldLimitWhenMaxDateOlder()
    {
        $maxEndDate = new EndDate($this->getMockConfig([]));
        $maxEndDate->forceMaxEndDate = '2018-03-19';
        $this->assertSame('2018-03-19', $maxEndDate->limitMaxEndDateIfNeeded('2019-01-02'));
    }
    private function getMockConfig(array $gaConfig)
    {
        $mock = $this->getMockBuilder(\Piwik\Config::class)->disableOriginalConstructor()->onlyMethods(['__get'])->getMock();
        $mock->method('__get')->willReturnCallback(function ($section) use($gaConfig) {
            if ($section != 'GoogleAnalyticsImporter') {
                return [];
            }
            return $gaConfig;
        });
        return $mock;
    }
}
