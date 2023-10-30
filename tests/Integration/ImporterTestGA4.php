<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration;

use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Plugins\GoogleAnalyticsImporter\ImporterGA4;
use Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
require_once PIWIK_INCLUDE_PATH . '/plugins/GoogleAnalyticsImporter/vendor/autoload.php';
/**
 * @group GoogleAnalyticsImporter
 * @group GoogleAnalyticsImporter_Integration
 */
class ImporterTestGA4 extends IntegrationTestCase
{
    public $mockData;
    /**
     * @var ImporterGA4
     */
    private $importer;
    public function setUp() : void
    {
        parent::setUp();
        $this->mockData = [];
        $this->importer = StaticContainer::get(ImporterGA4::class);
        $this->importer->setGAAdminClient($this->makeMockService());
    }
    public function test_importEntities_importsCorrectly()
    {
        $idSite = Fixture::createWebsite('2012-03-04 00:00:00');
        /** @var ImportStatus $importStatus */
        $importStatus = StaticContainer::get(ImportStatus::class);
        $importStatus->startingImport('properties/12345', 'accountid', '', $idSite, [['ga4Dimension' => 'userGender', 'dimensionScope' => 'visit']], 'ga4', 'streamId1');
        $this->setGaEntities();
        $this->importer->importEntities($idSite, 'properties/12345');
        $this->checkEntitiesCreated($idSite);
    }
    public function test_getRecentDatesToImport()
    {
        $startDate = Date::factory('2022-07-07');
        $endDate = Date::factory('2022-07-13');
        $dates = $this->importer->getRecentDatesToImport($startDate, $endDate->addDay(1), strtotime('2022-07-12'));
        $processed = [];
        foreach ($dates as $dateObj) {
            $processed[] = $dateObj->toString();
        }
        $this->assertEquals(['2022-07-11', '2022-07-10', '2022-07-09', '2022-07-08', '2022-07-07', '2022-07-12', '2022-07-13'], $processed);
    }
    public function test_getRecentDatesToImport_Past()
    {
        $startDate = Date::factory('2019-07-07');
        $endDate = Date::factory('2019-07-13');
        $dates = $this->importer->getRecentDatesToImport($startDate, $endDate->addDay(1), Date::now()->getTimestamp());
        $processed = [];
        foreach ($dates as $dateObj) {
            $processed[] = $dateObj->toString();
        }
        $this->assertEquals(['2019-07-13', '2019-07-12', '2019-07-11', '2019-07-10', '2019-07-09', '2019-07-08', '2019-07-07'], $processed);
    }
    public function makeMockService()
    {
        return new \Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\MockGoogleServiceAnalytics($this);
    }
    public function provideContainerConfig()
    {
        return ['GoogleAnalyticsImporter.googleAnalyticsAdminServiceClientClass' => $this->makeMockService()];
    }
    private function setGaEntities()
    {
        // goals
        $goal1 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\ConversionEvent(['name' => 'properties/12345/conversionEvents/345678910', 'event_name' => 'abc', 'create_time' => new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Timestamp(['seconds' => '10000', 'nanos' => '999']), 'deletable' => \true, 'custom' => \true]);
        $this->mockData['goals'] = [$goal1];
        // custom dimensions
        $customDim1 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\CustomDimension(['name' => 'properties/12345/customDimensions/3456789', 'parameter_name' => '', 'display_name' => 'cdim 1', 'description' => 'cdim 1 desc', 'scope' => 1]);
        $customDim2 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\CustomDimension(['name' => 'properties/12345/customDimensions/34567891', 'parameter_name' => '', 'display_name' => 'cdim 2', 'description' => 'cdim 2 desc', 'scope' => 2]);
        $customDim3 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\CustomDimension(['name' => 'properties/12345/customDimensions/34567892', 'parameter_name' => '', 'display_name' => 'cdim 3', 'description' => 'cdim 3 desc', 'scope' => 1]);
        $customDim4 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\CustomDimension(['name' => 'properties/12345/customDimensions/34567893', 'parameter_name' => '', 'display_name' => 'item_dimension', 'description' => 'scope item dimension desc', 'scope' => 3]);
        $this->mockData['customDimensions'] = [$customDim1, $customDim2, $customDim3, $customDim4];
    }
    protected static function configureFixture($fixture)
    {
        parent::configureFixture($fixture);
        $fixture->extraTestEnvVars['loadRealTranslations'] = \true;
    }
    private function checkEntitiesCreated($idSite)
    {
        $goals = \Piwik\Plugins\Goals\API::getInstance()->getGoals($idSite);
        $this->assertEquals([['idgoal' => '1', 'idsite' => '1', 'name' => 'abc', 'description' => '(imported from Google Analytics(GA4), original id = 345678910)', 'match_attribute' => 'event_name', 'pattern' => 'abc', 'pattern_type' => 'exact', 'case_sensitive' => '0', 'allow_multiple' => '0', 'revenue' => '0', 'deleted' => '0', 'event_value_as_revenue' => '0']], array_values($goals));
        $customDimensions = \Piwik\Plugins\CustomDimensions\API::getInstance()->getConfiguredCustomDimensions($idSite);
        $this->assertEquals([['idcustomdimension' => '1', 'idsite' => '1', 'name' => 'cdim 1', 'index' => '1', 'scope' => 'action', 'active' => \true, 'extractions' => [], 'case_sensitive' => \true], ['idcustomdimension' => '3', 'idsite' => '1', 'name' => 'cdim 3', 'index' => '2', 'scope' => 'action', 'active' => \true, 'extractions' => [], 'case_sensitive' => \true], ['idcustomdimension' => '2', 'idsite' => '1', 'name' => 'cdim 2', 'index' => '1', 'scope' => 'visit', 'active' => \true, 'extractions' => [], 'case_sensitive' => \true]], $customDimensions);
    }
}
class MockGoogleServiceAnalytics extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\AnalyticsAdminServiceClient
{
    /**
     * @var ImporterTest
     */
    protected $test;
    public function __construct(\Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\ImporterTestGA4 $test)
    {
        $this->test = $test;
        $this->listConversionEvents = new \Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\MockGaManagementGoals($test);
        $this->listCustomDimensions = new \Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\MockGaCustomDimensions($test);
    }
    public function listConversionEvents($parent, array $optionalArgs = [])
    {
        return new \Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\MockGaManagementGoals($this->test);
    }
    public function listCustomDimensions($parent, array $optionalArgs = [])
    {
        return new \Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\MockGaCustomDimensions($this->test);
    }
}
class MockGaManagementGoals
{
    /**
     * @var ImporterTest
     */
    protected $test;
    public function __construct(\Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\ImporterTestGA4 $test)
    {
        $this->test = $test;
    }
    public function iteratePages()
    {
        return [$this->test->mockData['goals']];
    }
}
class MockGaCustomDimensions
{
    /**
     * @var ImporterTest
     */
    protected $test;
    public function __construct(\Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\ImporterTestGA4 $test)
    {
        $this->test = $test;
    }
    public function iterateAllElements()
    {
        return $this->test->mockData['customDimensions'];
        $result = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\Analytics\CustomDimensions();
        $result->setItems($this->test->mockData['customDimensions']);
        return $result;
    }
}
