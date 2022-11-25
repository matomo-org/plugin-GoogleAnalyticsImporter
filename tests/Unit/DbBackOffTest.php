<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Unit;

use Google\Service\AnalyticsReporting;
use Monolog\Logger;
use Piwik\Date;
use Piwik\Option;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleAnalyticsGA4QueryService;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleAnalyticsQueryService;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleQueryObjectFactory;

/**
 * @group GoogleAnalyticsImporter
 * @group DbBackOffTest
 * @group Plugins
 */
class DbBackOffTest extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        // set up here if needed
    }
    
    public function tearDown(): void
    {
        // tear down here if needed
    }

    public function testGaImporterBackoff()
    {
        $oneHour = Date::factory('+1 hour')->getTimestamp();

        $queryService = $this->getMockBuilder(GoogleAnalyticsQueryService::class)
            ->onlyMethods(['setDbBackOff'])
            ->disableOriginalConstructor()
            ->getMock();
        $queryService->setDbBackOff('H');

        $this->assertEquals($oneHour, Option::get(GoogleAnalyticsQueryService::DELAY_OPTION_NAME));
    }

    public function testGa4ImporterBackoff()
    {
        $oneHour = Date::factory('+1 hour')->getTimestamp();

        $queryService = $this->getMockBuilder(GoogleAnalyticsGA4QueryService::class)
            ->onlyMethods(['setDbBackOff'])
            ->disableOriginalConstructor()
            ->getMock();
        $queryService->setDbBackOff('H');

        $this->assertEquals($oneHour, Option::get(GoogleAnalyticsGA4QueryService::DELAY_OPTION_NAME));
    }

}
