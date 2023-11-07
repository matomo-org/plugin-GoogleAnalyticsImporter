<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration;

use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Option;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleAnalyticsGA4QueryService;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleAnalyticsQueryService;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleQueryObjectFactory;
use Piwik\Plugins\SitesManager\API;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Log\LoggerInterface;
/**
 * @group GoogleAnalyticsImporter
 * @group DbBackOffTest
 * @group Plugins
 */
class DbBackOffTest extends IntegrationTestCase
{
    public function setUp() : void
    {
        parent::setUp();
        // set up your test here if needed
    }
    public function tearDown() : void
    {
        // clean up your test here if needed
        parent::tearDown();
    }
    public function testQueryService()
    {
        API::getInstance()->addSite("site1", ["http://piwik.net", "http://piwik.com/test/"]);
        $client = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Client();
        $mockReportingService = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\AnalyticsReporting($client);
        $builder = $this->getMockBuilder(\Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\AnalyticsReporting\Resource\Reports::class);
        $mockReportingService->reports = $builder->disableOriginalConstructor()->onlyMethods(['batchGet'])->getMock();
        $this->getMockBuilder(\Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\AnalyticsReporting::class);
        $gaQueryService = new GoogleAnalyticsQueryService($mockReportingService, 'testviewid', [], 1, 'testuser', StaticContainer::get(GoogleQueryObjectFactory::class), StaticContainer::get(LoggerInterface::class));
        $gaQueryService->setDbBackOff();
        $this->assertSame(Date::factory('+1 hour')->toString('Y-m-d H:i'), Date::factory(Option::get(GoogleAnalyticsQueryService::DELAY_OPTION_NAME))->toString('Y-m-d H:i'));
        $gaQueryService->setDbBackOff('D');
        $this->assertSame(Date::factory('tomorrow')->toString('Y-m-d H:i'), Date::factory(Option::get(GoogleAnalyticsQueryService::DELAY_OPTION_NAME))->toString('Y-m-d H:i'));
    }
}
