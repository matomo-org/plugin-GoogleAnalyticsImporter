<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration;

use Piwik\Container\StaticContainer;
use Piwik\Plugins\GoogleAnalyticsImporter\Importer;
use Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

require_once PIWIK_INCLUDE_PATH . '/plugins/GoogleAnalyticsImporter/vendor/autoload.php';

class ImporterTest extends IntegrationTestCase
{
    public $mockData;

    /**
     * @var Importer
     */
    private $importer;

    public function setUp(): void
    {
        parent::setUp();
        $this->mockData = [];
        $this->importer = StaticContainer::get(Importer::class);
    }

    public function test_importEntities_importsCorrectly()
    {
        $idSite = Fixture::createWebsite('2012-03-04 00:00:00');

        /** @var ImportStatus $importStatus */
        $importStatus = StaticContainer::get(ImportStatus::class);
        $importStatus->startingImport('propertyid', 'accountid', 'viewid', $idSite, [
            ['gaDimension' => 'ga:userGender', 'dimensionScope' => 'visit'],
        ]);

        $this->setGaEntities();

        $this->importer->importEntities($idSite, 'accountid', 'propertyid', 'viewid');

        $this->checkEntitiesCreated($idSite);

        $this->importer->importEntities($idSite, 'accountid', 'propertyid', 'viewid');

        $this->checkEntitiesCreated($idSite);
    }

    public function makeMockService()
    {
        return new MockGoogleServiceAnalytics($this);
    }

    public function provideContainerConfig()
    {
        return [
            \Google_Service_Analytics::class => $this->makeMockService(),
        ];
    }

    private function setGaEntities()
    {
        // goals

        // event goal
        $goal1 = new \Google_Service_Analytics_Goal();
        $goal1->setId(5);
        $goal1->setName('goal 1');
        $eventDetails = new \Google_Service_Analytics_GoalEventDetails();
        $condition = new \Google_Service_Analytics_GoalEventDetailsEventConditions();
        $condition->setType('category');
        $condition->setMatchType('regexp');
        $condition->setExpression('abc');
        $eventDetails->setEventConditions([$condition]);
        $goal1->setEventDetails($eventDetails);

        // url destination goal
        $goal2 = new \Google_Service_Analytics_Goal();
        $goal2->setId(6);
        $goal2->setName('goal 2');
        $urlDestinationDetails = new \Google_Service_Analytics_GoalUrlDestinationDetails();
        $urlDestinationDetails->setMatchType('head');
        $urlDestinationDetails->setUrl('def');
        $urlDestinationDetails->setCaseSensitive(true);
        $goal2->setUrlDestinationDetails($urlDestinationDetails);

        // time on site goal
        $goal3 = new \Google_Service_Analytics_Goal();
        $goal3->setId(7);
        $goal3->setName('goal 3');
        $visitTimeOnSiteDetails = new \Google_Service_Analytics_GoalVisitTimeOnSiteDetails();
        $visitTimeOnSiteDetails->setComparisonType('greater_than');
        $visitTimeOnSiteDetails->setComparisonValue(45);
        $goal3->setVisitTimeOnSiteDetails($visitTimeOnSiteDetails);

        // broken goal (unsupported goal type)
        $goal4 = new \Google_Service_Analytics_Goal();
        $goal4->setName('goal 4');
        $goal4->setId(8);

        // event goal w/ multiple criteria
        $goal5 = new \Google_Service_Analytics_Goal();
        $goal5->setName('goal 5');
        $goal5->setId(9);
        $eventDetails = new \Google_Service_Analytics_GoalEventDetails();
        $condition1 = new \Google_Service_Analytics_GoalEventDetailsEventConditions();
        $condition1->setType('category');
        $condition1->setMatchType('regexp');
        $condition1->setExpression('abc');
        $eventDetails->setEventConditions([$condition1, $condition1]);
        $goal5->setEventDetails($eventDetails);

        $this->mockData['goals'] = [$goal1, $goal2, $goal3, $goal4, $goal5];

        // custom dimensions
        $customDim1 = new \Google_Service_Analytics_CustomDimension();
        $customDim1->setId('ga:dimension1');
        $customDim1->setName('cdim 1');
        $customDim1->setActive(true);
        $customDim1->setScope('hit');

        $customDim2 = new \Google_Service_Analytics_CustomDimension();
        $customDim2->setId('ga:dimension2');
        $customDim2->setName('cdim < 2');
        $customDim2->setActive(false);
        $customDim2->setScope('session');

        $customDim3 = new \Google_Service_Analytics_CustomDimension();
        $customDim3->setId('ga:dimension3');
        $customDim3->setName('cdim & 3');
        $customDim3->setActive(true);
        $customDim3->setScope('user');

        $this->mockData['customDimensions'] = [$customDim1, $customDim2, $customDim3];
    }

    protected static function configureFixture($fixture)
    {
        parent::configureFixture($fixture);
        $fixture->extraTestEnvVars['loadRealTranslations'] = true;
    }

    private function checkEntitiesCreated($idSite)
    {
        $goals = \Piwik\Plugins\Goals\API::getInstance()->getGoals($idSite);
        $this->assertEquals([
            [
                'idgoal' => '1',
                'idsite' => '1',
                'name' => 'goal 1',
                'description' => '(imported from Google Analytics, original id = 5)',
                'match_attribute' => 'event_category',
                'pattern' => 'abc',
                'pattern_type' => 'regex',
                'case_sensitive' => '0',
                'allow_multiple' => '0',
                'revenue' => '0',
                'deleted' => '0',
                'event_value_as_revenue' => '0',
            ],
            [
                'idgoal' => '2',
                'idsite' => '1',
                'name' => 'goal 2',
                'description' => '(imported from Google Analytics, original id = 6)',
                'match_attribute' => 'url',
                'pattern' => '^def',
                'pattern_type' => 'regex',
                'case_sensitive' => '1',
                'allow_multiple' => '0',
                'revenue' => '0',
                'deleted' => '0',
                'event_value_as_revenue' => '0',
            ],
            [
                'idgoal' => '3',
                'idsite' => '1',
                'name' => 'goal 3',
                'description' => '(imported from Google Analytics, original id = 7)',
                'match_attribute' => 'visit_duration',
                'pattern' => '45',
                'pattern_type' => 'greater_than',
                'case_sensitive' => '0',
                'allow_multiple' => '0',
                'revenue' => '0',
                'deleted' => '0',
                'event_value_as_revenue' => '0',
            ],
            [
                'idgoal' => '4',
                'idsite' => '1',
                'name' => 'goal 4',
                'description' => '(imported from Google Analytics, original id = 8)',
                'match_attribute' => 'manually',
                'allow_multiple' => '0',
                'revenue' => '0',
                'deleted' => '0',
                'event_value_as_revenue' => '0',
            ],
            [
                'idgoal' => '5',
                'idsite' => '1',
                'name' => 'goal 5',
                'description' => '(imported from Google Analytics, original id = 9)',
                'match_attribute' => 'manually',
                'allow_multiple' => '0',
                'revenue' => '0',
                'deleted' => '0',
                'event_value_as_revenue' => '0',
            ],
        ], array_values($goals));

        $customDimensions = \Piwik\Plugins\CustomDimensions\API::getInstance()->getConfiguredCustomDimensions($idSite);
        $this->assertEquals([
            [
                'idcustomdimension' => '1',
                'idsite' => '1',
                'name' => 'cdim 1',
                'index' => '1',
                'scope' => 'action',
                'active' => true,
                'extractions' => [],
                'case_sensitive' => true,
            ],
            [
                'idcustomdimension' => '2',
                'idsite' => '1',
                'name' => 'cdim  2',
                'index' => '1',
                'scope' => 'visit',
                'active' => false,
                'extractions' => [],
                'case_sensitive' => true,
            ],
            [
                'idcustomdimension' => '3',
                'idsite' => '1',
                'name' => 'ga:userGender',
                'index' => '2',
                'scope' => 'visit',
                'active' => true,
                'extractions' => [],
                'case_sensitive' => true,
            ],
        ], $customDimensions);
    }
}

class MockGoogleServiceAnalytics extends \Google_Service_Analytics
{
    public function __construct(ImporterTest $test)
    {
        $this->management_goals = new MockGaManagementGoals($test);
        $this->management_customDimensions = new MockGaCustomDimensions($test);
    }
}

class MockGaManagementGoals
{
    public function __construct(ImporterTest $test)
    {
        $this->test = $test;
    }

    public function listManagementGoals()
    {
        $result = new \Google_Service_Analytics_Goals();
        $result->setItems($this->test->mockData['goals']);
        return $result;
    }
}

class MockGaCustomDimensions
{
    public function __construct(ImporterTest $test)
    {
        $this->test = $test;
    }

    public function listManagementCustomDimensions()
    {
        $result = new \Google_Service_Analytics_CustomDimensions();
        $result->setItems($this->test->mockData['customDimensions']);
        return $result;
    }
}