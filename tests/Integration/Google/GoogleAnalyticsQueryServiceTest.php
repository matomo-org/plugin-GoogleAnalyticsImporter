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
use Piwik\Log\LoggerInterface;
class GoogleAnalyticsQueryServiceTest extends IntegrationTestCase
{
    public function getTestDataForGaErrorTest()
    {
        return [[new \Exception(json_encode(['error' => ['message' => 'this is a test exception']]), 503), 'Failed to reach GA after 2 attempt(s). The import will automatically restart later and you don\'t need to do anything. Last GA error message: this is a test exception'], [new \Exception(json_encode(['error' => []]), 503), 'Failed to reach GA after 2 attempt(s). The import will automatically restart later and you don\'t need to do anything.'], [new \Exception('lakjdsflsdj', 503), 'Failed to reach GA after 2 attempt(s). The import will automatically restart later and you don\'t need to do anything.'], [new \Exception(json_encode(['error' => ['message' => 'Unknown metric(s): blah, blah and blah']]), 400), 'Failed to reach GA after 2 attempt(s). The import will automatically restart later and you don\'t need to do anything. Last GA error message: Unknown metric(s): blah, blah and blah'], [new \Exception(json_encode(['error' => ['message' => 'this is a test 400 exception']]), 401), '{"error":{"message":"this is a test 400 exception"}}'], [new \Exception(json_encode(['error' => ['message' => 'this is a test 403 exception']]), 403), 'Failed to reach GA after 1 attempt(s). The import will automatically restart later and you don\'t need to do anything. Last GA error message: this is a test 403 exception'], [new \Exception(json_encode(['error' => ['message' => 'this is a test 500 exception']]), 500), 'Failed to reach GA after 2 attempt(s). The import will automatically restart later and you don\'t need to do anything. Last GA error message: this is a test 500 exception']];
    }
    /**
     * @dataProvider getTestDataForGaErrorTest
     */
    public function test_query_returnsGaMessageWhenGaReturnsPersistentError($testEx, $expectedMessage)
    {
        Fixture::createWebsite('2012-02-02 00:00:00');
        $client = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Client();
        $mockReportingService = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\AnalyticsReporting($client);
        $builder = $this->getMockBuilder(\Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\AnalyticsReporting\Resource\Reports::class);
        $mockReportingService->reports = $builder->disableOriginalConstructor()->onlyMethods(['batchGet'])->getMock();
        $mockReportingService->reports->method('batchGet')->willThrowException($testEx);
        $this->getMockBuilder(\Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\AnalyticsReporting::class);
        $gaQueryService = new GoogleAnalyticsQueryService($mockReportingService, 'testviewid', [], 1, 'testuser', StaticContainer::get(GoogleQueryObjectFactory::class), StaticContainer::get(LoggerInterface::class));
        if ($testEx->getCode() === 400) {
            $gaQueryService->setMaxAttempts(2);
        }
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($expectedMessage);
        $gaQueryService->query(Date::factory('2020-03-04'), ['somedimension'], [Metrics::INDEX_NB_VISITS]);
    }
}
