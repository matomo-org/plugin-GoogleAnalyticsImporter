<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\Google;


use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Metrics;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleAnalyticsQueryService;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleQueryObjectFactory;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Psr\Log\LoggerInterface;

class GoogleAnalyticsQueryServiceTest extends IntegrationTestCase
{
    public function getTestDataForGaErrorTest()
    {
        return [
            [new \Exception(json_encode([
                'error' => [
                    'message' => 'this is a test exception',
                ],
            ]), 503), 'Failed to reach GA after 2 attempts. Restart the import later. Last GA error message: this is a test exception'],
            [new \Exception(json_encode([
                'error' => [
                ],
            ]), 503), 'Failed to reach GA after 2 attempts. Restart the import later.'],
            [new \Exception('lakjdsflsdj', 503), 'Failed to reach GA after 2 attempts. Restart the import later.'],
        ];
    }

    /**
     * @dataProvider getTestDataForGaErrorTest
     */
    public function test_query_returnsGaMessageWhenGaReturnsPersistentError($testEx, $expectedMessage)
    {
        Fixture::createWebsite('2012-02-02 00:00:00');

        $client = new \Google_Client();
        $mockReportingService = new \Google_Service_AnalyticsReporting($client);

        $builder = $this->getMockBuilder(\Google_Service_AnalyticsReporting_Resource_Reports::class);
        $mockReportingService->reports = $builder
            ->disableOriginalConstructor()
            ->onlyMethods([ 'batchGet' ])
            ->getMock();
        $mockReportingService->reports->method('batchGet')->willThrowException($testEx);

        $this->getMockBuilder(\Google_Service_AnalyticsReporting::class);
        $gaQueryService = new GoogleAnalyticsQueryService($mockReportingService, 'testviewid', [], 1, 'testuser',
            StaticContainer::get(GoogleQueryObjectFactory::class), StaticContainer::get(LoggerInterface::class));
        $gaQueryService->setMaxAttempts(2);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($expectedMessage);

        $gaQueryService->query(Date::factory('2020-03-04'), ['somedimension'], [Metrics::INDEX_NB_VISITS]);
    }
}