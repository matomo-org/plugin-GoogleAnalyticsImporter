<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration;

use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Option;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleAnalyticsGA4QueryService;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleAnalyticsQueryService;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleQueryObjectFactory;
use Piwik\Plugins\SitesManager\API;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Psr\Log\LoggerInterface;

/**
 * @group GoogleAnalyticsImporter
 * @group DbBackOffTest
 * @group Plugins
 */
class DbBackOffTest extends IntegrationTestCase
{

    public function setUp(): void
    {
        parent::setUp();

        // set up your test here if needed
    }

    public function tearDown(): void
    {
        // clean up your test here if needed

        parent::tearDown();
    }

    public function testQueryService()
    {
        $oneHour = Date::factory('+1 hour')->getTimestamp();
        API::getInstance()->addSite("site1", ["http://piwik.net", "http://piwik.com/test/"]);

        $client = new \Google\Client();
        $mockReportingService = new \Google\Service\AnalyticsReporting($client);

        $builder = $this->getMockBuilder(\Google\Service\AnalyticsReporting\Resource\Reports::class);
        $mockReportingService->reports = $builder
            ->disableOriginalConstructor()
            ->onlyMethods(['batchGet'])
            ->getMock();

        $this->getMockBuilder(\Google\Service\AnalyticsReporting::class);
        $gaQueryService = new GoogleAnalyticsQueryService($mockReportingService, 'testviewid', [], 1, 'testuser',
            StaticContainer::get(GoogleQueryObjectFactory::class), StaticContainer::get(LoggerInterface::class));
        $gaQueryService->setDbBackOff();

        $this->assertLessThan(1, abs($oneHour - Option::get(GoogleAnalyticsQueryService::DELAY_OPTION_NAME)));

        $tomorrow = Date::factory('tomorrow')->getTimestamp();
        $gaQueryService->setDbBackOff('D');
        $this->assertLessThan(1, abs($tomorrow - Option::get(GoogleAnalyticsQueryService::DELAY_OPTION_NAME)));
    }

}
