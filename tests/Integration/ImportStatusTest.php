<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration;

use Piwik\Date;
use Piwik\Db;
use Piwik\Option;
use Piwik\Plugins\GoogleAnalyticsImporter\Commands\ImportReports;
use Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus;
use Piwik\Site;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
/**
 * @group GoogleAnalyticsImporter
 * @group GoogleAnalyticsImporter_Integration
 */
class ImportStatusTest extends IntegrationTestCase
{
    /**
     * @var ImportStatus
     */
    private $instance;
    public function setUp() : void
    {
        parent::setUp();
        Fixture::createWebsite('2012-01-02 00:00:00');
        $this->instance = new ImportStatus();
    }
    public function test_workflow()
    {
        Date::$now = Date::factory('2015-03-04 00:00:00')->getTimestamp();
        $idSite = 5;
        $status = $this->getImportStatus($idSite);
        $this->assertEmpty($status);
        $this->instance->startingImport('property', 'account', 'view', $idSite, [['gaDimension' => 'ga:whatever', 'dimensionScope' => 'visit']]);
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_STARTED, 'idSite' => $idSite, 'ga' => ['property' => 'property', 'account' => 'account', 'view' => 'view', 'import_type' => 'Universal Analytics'], 'last_date_imported' => null, 'import_start_time' => Date::$now, 'import_end_time' => null, 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [['gaDimension' => 'ga:whatever', 'dimensionScope' => 'visit']], 'days_finished_since_rate_limit' => 0, 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \false, 'streamIds' => []], $status);
        $this->instance->setImportDateRange($idSite, null, null);
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_STARTED, 'idSite' => $idSite, 'ga' => ['import_type' => 'Universal Analytics', 'property' => 'property', 'account' => 'account', 'view' => 'view'], 'last_date_imported' => null, 'import_start_time' => Date::$now, 'import_end_time' => null, 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [['gaDimension' => 'ga:whatever', 'dimensionScope' => 'visit']], 'days_finished_since_rate_limit' => 0, 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \false, 'streamIds' => []], $status);
        $this->instance->setImportDateRange($idSite, Date::factory('2012-03-04'), Date::factory('2012-03-05'));
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_STARTED, 'idSite' => $idSite, 'ga' => ['import_type' => 'Universal Analytics', 'property' => 'property', 'account' => 'account', 'view' => 'view'], 'last_date_imported' => null, 'import_start_time' => Date::$now, 'import_end_time' => null, 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => '2012-03-04', 'import_range_end' => '2012-03-05', 'extra_custom_dimensions' => [['gaDimension' => 'ga:whatever', 'dimensionScope' => 'visit']], 'days_finished_since_rate_limit' => 0, 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \false, 'streamIds' => []], $status);
        $this->instance->setImportDateRange($idSite, Date::factory('2017-03-04'), null);
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_STARTED, 'idSite' => $idSite, 'ga' => ['import_type' => 'Universal Analytics', 'property' => 'property', 'account' => 'account', 'view' => 'view'], 'last_date_imported' => null, 'import_start_time' => Date::$now, 'import_end_time' => null, 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => '2017-03-04', 'import_range_end' => '', 'extra_custom_dimensions' => [['gaDimension' => 'ga:whatever', 'dimensionScope' => 'visit']], 'days_finished_since_rate_limit' => 0, 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \false, 'streamIds' => []], $status);
        $this->instance->dayImportFinished($idSite, Date::factory('2015-03-02'));
        $status = $this->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_ONGOING, 'idSite' => $idSite, 'ga' => ['import_type' => 'Universal Analytics', 'property' => 'property', 'account' => 'account', 'view' => 'view'], 'last_date_imported' => '2015-03-02', 'import_start_time' => Date::$now, 'import_end_time' => null, 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => '2017-03-04', 'import_range_end' => '', 'extra_custom_dimensions' => [['gaDimension' => 'ga:whatever', 'dimensionScope' => 'visit']], 'days_finished_since_rate_limit' => 1, 'reimport_ranges' => [], 'main_import_progress' => '2015-03-02', 'isGA4' => \false, 'streamIds' => []], $status);
        $this->instance->dayImportFinished($idSite, Date::factory('2015-03-04'));
        $this->instance->dayImportFinished($idSite, Date::factory('2015-03-03'));
        // test it won't set to 03
        $status = $this->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_ONGOING, 'idSite' => $idSite, 'ga' => ['import_type' => 'Universal Analytics', 'property' => 'property', 'account' => 'account', 'view' => 'view'], 'last_date_imported' => '2015-03-02', 'import_start_time' => Date::$now, 'import_end_time' => null, 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => '2017-03-04', 'import_range_end' => '', 'extra_custom_dimensions' => [['gaDimension' => 'ga:whatever', 'dimensionScope' => 'visit']], 'days_finished_since_rate_limit' => 3, 'reimport_ranges' => [], 'main_import_progress' => '2015-03-02', 'isGA4' => \false, 'streamIds' => []], $status);
        $this->instance->finishedImport($idSite);
        $status = $this->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_FINISHED, 'idSite' => $idSite, 'ga' => ['import_type' => 'Universal Analytics', 'property' => 'property', 'account' => 'account', 'view' => 'view'], 'last_date_imported' => '2015-03-02', 'import_start_time' => Date::$now, 'import_end_time' => Date::$now, 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => '2017-03-04', 'import_range_end' => '', 'extra_custom_dimensions' => [['gaDimension' => 'ga:whatever', 'dimensionScope' => 'visit']], 'days_finished_since_rate_limit' => 3, 'reimport_ranges' => [], 'main_import_progress' => '2015-03-02', 'isGA4' => \false, 'streamIds' => []], $status);
        $this->instance->deleteStatus($idSite);
        $status = $this->getImportStatus($idSite);
        $this->assertEmpty($status);
    }
    public function test_workflowGA4()
    {
        Date::$now = Date::factory('2015-03-04 00:00:00')->getTimestamp();
        $idSite = 5;
        $status = $this->getImportStatus($idSite);
        $this->assertEmpty($status);
        $this->instance->startingImport('properties/1234', 'account', '', $idSite, [['gaDimension' => 'whatever', 'dimensionScope' => 'visit']], 'ga4');
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_STARTED, 'idSite' => $idSite, 'ga' => ['property' => 'properties/1234', 'account' => 'account', 'view' => '', 'import_type' => 'GA4'], 'last_date_imported' => null, 'import_start_time' => Date::$now, 'import_end_time' => null, 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [['gaDimension' => 'whatever', 'dimensionScope' => 'visit']], 'days_finished_since_rate_limit' => 0, 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \true, 'streamIds' => []], $status);
        $this->instance->setImportDateRange($idSite, null, null);
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_STARTED, 'idSite' => $idSite, 'ga' => ['import_type' => 'GA4', 'property' => 'properties/1234', 'account' => 'account', 'view' => ''], 'last_date_imported' => null, 'import_start_time' => Date::$now, 'import_end_time' => null, 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [['gaDimension' => 'whatever', 'dimensionScope' => 'visit']], 'days_finished_since_rate_limit' => 0, 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \true, 'streamIds' => []], $status);
        $this->instance->setImportDateRange($idSite, Date::factory('2012-03-04'), Date::factory('2012-03-05'));
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_STARTED, 'idSite' => $idSite, 'ga' => ['import_type' => 'GA4', 'property' => 'properties/1234', 'account' => 'account', 'view' => ''], 'last_date_imported' => null, 'import_start_time' => Date::$now, 'import_end_time' => null, 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => '2012-03-04', 'import_range_end' => '2012-03-05', 'extra_custom_dimensions' => [['gaDimension' => 'whatever', 'dimensionScope' => 'visit']], 'days_finished_since_rate_limit' => 0, 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \true, 'streamIds' => []], $status);
        $this->instance->setImportDateRange($idSite, Date::factory('2017-03-04'), null);
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_STARTED, 'idSite' => $idSite, 'ga' => ['import_type' => 'GA4', 'property' => 'properties/1234', 'account' => 'account', 'view' => ''], 'last_date_imported' => null, 'import_start_time' => Date::$now, 'import_end_time' => null, 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => '2017-03-04', 'import_range_end' => '', 'extra_custom_dimensions' => [['gaDimension' => 'whatever', 'dimensionScope' => 'visit']], 'days_finished_since_rate_limit' => 0, 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \true, 'streamIds' => []], $status);
        $this->instance->dayImportFinished($idSite, Date::factory('2015-03-02'));
        $status = $this->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_ONGOING, 'idSite' => $idSite, 'ga' => ['import_type' => 'GA4', 'property' => 'properties/1234', 'account' => 'account', 'view' => ''], 'last_date_imported' => '2015-03-02', 'import_start_time' => Date::$now, 'import_end_time' => null, 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => '2017-03-04', 'import_range_end' => '', 'extra_custom_dimensions' => [['gaDimension' => 'whatever', 'dimensionScope' => 'visit']], 'days_finished_since_rate_limit' => 1, 'reimport_ranges' => [], 'main_import_progress' => '2015-03-02', 'isGA4' => \true, 'streamIds' => []], $status);
        $this->instance->dayImportFinished($idSite, Date::factory('2015-03-04'));
        $this->instance->dayImportFinished($idSite, Date::factory('2015-03-03'));
        // test it won't set to 03
        $status = $this->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_ONGOING, 'idSite' => $idSite, 'ga' => ['import_type' => 'GA4', 'property' => 'properties/1234', 'account' => 'account', 'view' => ''], 'last_date_imported' => '2015-03-02', 'import_start_time' => Date::$now, 'import_end_time' => null, 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => '2017-03-04', 'import_range_end' => '', 'extra_custom_dimensions' => [['gaDimension' => 'whatever', 'dimensionScope' => 'visit']], 'days_finished_since_rate_limit' => 3, 'reimport_ranges' => [], 'main_import_progress' => '2015-03-02', 'isGA4' => \true, 'streamIds' => []], $status);
        $this->instance->finishedImport($idSite);
        $status = $this->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_FINISHED, 'idSite' => $idSite, 'ga' => ['import_type' => 'GA4', 'property' => 'properties/1234', 'account' => 'account', 'view' => ''], 'last_date_imported' => '2015-03-02', 'import_start_time' => Date::$now, 'import_end_time' => Date::$now, 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => '2017-03-04', 'import_range_end' => '', 'extra_custom_dimensions' => [['gaDimension' => 'whatever', 'dimensionScope' => 'visit']], 'days_finished_since_rate_limit' => 3, 'reimport_ranges' => [], 'main_import_progress' => '2015-03-02', 'isGA4' => \true, 'streamIds' => []], $status);
        $this->instance->deleteStatus($idSite);
        $status = $this->getImportStatus($idSite);
        $this->assertEmpty($status);
    }
    public function test_error_workflow()
    {
        Date::$now = Date::factory('2015-03-04 00:00:00')->getTimestamp();
        $idSite = 5;
        $status = $this->getImportStatus($idSite);
        $this->assertEmpty($status);
        $this->instance->startingImport('property', 'account', 'view', $idSite);
        $status = $this->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_STARTED, 'idSite' => $idSite, 'ga' => ['import_type' => 'Universal Analytics', 'property' => 'property', 'account' => 'account', 'view' => 'view'], 'last_date_imported' => null, 'import_start_time' => Date::$now, 'import_end_time' => null, 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [], 'days_finished_since_rate_limit' => 0, 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \false, 'streamIds' => []], $status);
        $this->instance->erroredImport($idSite, 'test error message');
        $status = $this->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_ERRORED, 'idSite' => $idSite, 'ga' => ['import_type' => 'Universal Analytics', 'property' => 'property', 'account' => 'account', 'view' => 'view'], 'last_date_imported' => null, 'import_start_time' => Date::$now, 'import_end_time' => null, 'error' => 'test error message', 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [], 'days_finished_since_rate_limit' => 0, 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \false, 'streamIds' => []], $status);
    }
    public function test_error_workflowGA4()
    {
        Date::$now = Date::factory('2015-03-04 00:00:00')->getTimestamp();
        $idSite = 5;
        $status = $this->getImportStatus($idSite);
        $this->assertEmpty($status);
        $this->instance->startingImport('properties/1234', 'account', '', $idSite, [], 'ga4');
        $status = $this->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_STARTED, 'idSite' => $idSite, 'ga' => ['import_type' => 'GA4', 'property' => 'properties/1234', 'account' => 'account', 'view' => ''], 'last_date_imported' => null, 'import_start_time' => Date::$now, 'import_end_time' => null, 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [], 'days_finished_since_rate_limit' => 0, 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \true, 'streamIds' => []], $status);
        $this->instance->erroredImport($idSite, 'test error message');
        $status = $this->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_ERRORED, 'idSite' => $idSite, 'ga' => ['import_type' => 'GA4', 'property' => 'properties/1234', 'account' => 'account', 'view' => ''], 'last_date_imported' => null, 'import_start_time' => Date::$now, 'import_end_time' => null, 'error' => 'test error message', 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [], 'days_finished_since_rate_limit' => 0, 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \true, 'streamIds' => []], $status);
    }
    public function test_rateLimited_workflow()
    {
        Date::$now = Date::factory('2015-03-04 00:00:00')->getTimestamp();
        $idSite = 5;
        $status = $this->getImportStatus($idSite);
        $this->assertEmpty($status);
        $this->instance->startingImport('property', 'account', 'view', $idSite);
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_STARTED, 'idSite' => $idSite, 'ga' => ['import_type' => 'Universal Analytics', 'property' => 'property', 'account' => 'account', 'view' => 'view'], 'last_date_imported' => null, 'import_start_time' => Date::$now, 'import_end_time' => null, 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [], 'days_finished_since_rate_limit' => 0, 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \false, 'streamIds' => []], $status);
        $this->instance->rateLimitReached($idSite);
        $status = $this->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_RATE_LIMITED, 'idSite' => $idSite, 'ga' => ['import_type' => 'Universal Analytics', 'property' => 'property', 'account' => 'account', 'view' => 'view'], 'last_date_imported' => null, 'import_start_time' => Date::$now, 'import_end_time' => null, 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [], 'days_finished_since_rate_limit' => 0, 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \false, 'streamIds' => []], $status);
    }
    public function test_internal_rateLimited_workflow()
    {
        Date::$now = Date::factory('2022-11-01 00:00:00')->getTimestamp();
        $idSite = 5;
        $status = $this->getImportStatus($idSite);
        $this->assertEmpty($status);
        $this->instance->startingImport('property', 'account', 'view', $idSite);
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_STARTED, 'idSite' => $idSite, 'ga' => ['import_type' => 'Universal Analytics', 'property' => 'property', 'account' => 'account', 'view' => 'view'], 'last_date_imported' => null, 'import_start_time' => Date::$now, 'import_end_time' => null, 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [], 'days_finished_since_rate_limit' => 0, 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \false, 'streamIds' => []], $status);
        $this->instance->cloudRateLimitReached($idSite, 'Test Error Message');
        $status = $this->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_CLOUD_RATE_LIMITED, 'idSite' => $idSite, 'ga' => ['import_type' => 'Universal Analytics', 'property' => 'property', 'account' => 'account', 'view' => 'view'], 'last_date_imported' => null, 'import_start_time' => Date::$now, 'import_end_time' => null, 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [], 'days_finished_since_rate_limit' => 0, 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \false, 'streamIds' => [], 'error' => 'Test Error Message'], $status);
    }
    public function test_rateLimited_Hourly_workflowGA4()
    {
        Date::$now = Date::factory('2015-03-04 00:00:00')->getTimestamp();
        $idSite = 5;
        $status = $this->getImportStatus($idSite);
        $this->assertEmpty($status);
        $this->instance->startingImport('properties/1234', 'account', '', $idSite, [], 'ga4');
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_STARTED, 'idSite' => $idSite, 'ga' => ['import_type' => 'GA4', 'property' => 'properties/1234', 'account' => 'account', 'view' => ''], 'last_date_imported' => null, 'import_start_time' => Date::$now, 'import_end_time' => null, 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [], 'days_finished_since_rate_limit' => 0, 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \true, 'streamIds' => []], $status);
        $this->instance->rateLimitReachedHourly($idSite);
        $status = $this->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_RATE_LIMITED_HOURLY, 'idSite' => $idSite, 'ga' => ['import_type' => 'GA4', 'property' => 'properties/1234', 'account' => 'account', 'view' => ''], 'last_date_imported' => null, 'import_start_time' => Date::$now, 'import_end_time' => null, 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [], 'days_finished_since_rate_limit' => 0, 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \true, 'streamIds' => []], $status);
    }
    public function test_rateLimited_workflowGA4()
    {
        Date::$now = Date::factory('2015-03-04 00:00:00')->getTimestamp();
        $idSite = 5;
        $status = $this->getImportStatus($idSite);
        $this->assertEmpty($status);
        $this->instance->startingImport('properties/1234', 'account', '', $idSite, [], 'ga4');
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_STARTED, 'idSite' => $idSite, 'ga' => ['import_type' => 'GA4', 'property' => 'properties/1234', 'account' => 'account', 'view' => ''], 'last_date_imported' => null, 'import_start_time' => Date::$now, 'import_end_time' => null, 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [], 'days_finished_since_rate_limit' => 0, 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \true, 'streamIds' => []], $status);
        $this->instance->rateLimitReached($idSite);
        $status = $this->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_RATE_LIMITED, 'idSite' => $idSite, 'ga' => ['import_type' => 'GA4', 'property' => 'properties/1234', 'account' => 'account', 'view' => ''], 'last_date_imported' => null, 'import_start_time' => Date::$now, 'import_end_time' => null, 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [], 'days_finished_since_rate_limit' => 0, 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \true, 'streamIds' => []], $status);
    }
    public function test_internal_rateLimited_workflowGA4()
    {
        Date::$now = Date::factory('2015-03-04 00:00:00')->getTimestamp();
        $idSite = 5;
        $status = $this->getImportStatus($idSite);
        $this->instance->startingImport('properties/1234', 'account', '', $idSite, [], 'ga4');
        $this->instance->cloudRateLimitReached($idSite, 'Test Message');
        $status = $this->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_CLOUD_RATE_LIMITED, 'idSite' => $idSite, 'ga' => ['import_type' => 'GA4', 'property' => 'properties/1234', 'account' => 'account', 'view' => ''], 'last_date_imported' => null, 'import_start_time' => Date::$now, 'import_end_time' => null, 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [], 'days_finished_since_rate_limit' => 0, 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \true, 'streamIds' => [], 'error' => 'Test Message'], $status);
    }
    public function test_future_date_import_pending_workflow()
    {
        Date::$now = Date::factory('2022-11-01 00:00:00')->getTimestamp();
        $idSite = 5;
        $status = $this->getImportStatus($idSite);
        $this->assertEmpty($status);
        $this->instance->startingImport('property', 'account', 'view', $idSite);
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_STARTED, 'idSite' => $idSite, 'ga' => ['import_type' => 'Universal Analytics', 'property' => 'property', 'account' => 'account', 'view' => 'view'], 'last_date_imported' => null, 'import_start_time' => Date::$now, 'import_end_time' => null, 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [], 'days_finished_since_rate_limit' => 0, 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \false, 'streamIds' => []], $status);
        $this->instance->futureDateImportDetected($idSite, '2023-04-18');
        $status = $this->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_FUTURE_DATE_IMPORT_PENDING, 'idSite' => $idSite, 'ga' => ['import_type' => 'Universal Analytics', 'property' => 'property', 'account' => 'account', 'view' => 'view'], 'last_date_imported' => null, 'import_start_time' => Date::$now, 'import_end_time' => null, 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [], 'days_finished_since_rate_limit' => 0, 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \false, 'streamIds' => [], 'future_resume_date' => '2023-04-18'], $status);
    }
    public function test_future_date_import_pending_workflowGA4()
    {
        Date::$now = Date::factory('2015-03-04 00:00:00')->getTimestamp();
        $idSite = 5;
        $status = $this->getImportStatus($idSite);
        $this->instance->startingImport('properties/1234', 'account', '', $idSite, [], 'ga4');
        $this->instance->futureDateImportDetected($idSite, '2023-04-18');
        $status = $this->getImportStatus($idSite);
        $this->assertEquals(['status' => ImportStatus::STATUS_FUTURE_DATE_IMPORT_PENDING, 'idSite' => $idSite, 'ga' => ['import_type' => 'GA4', 'property' => 'properties/1234', 'account' => 'account', 'view' => ''], 'last_date_imported' => null, 'import_start_time' => Date::$now, 'import_end_time' => null, 'last_job_start_time' => Date::$now, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [], 'days_finished_since_rate_limit' => 0, 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \true, 'streamIds' => [], 'future_resume_date' => '2023-04-18'], $status);
    }
    /**
     * @dataProvider getTestDataForGetEstimatedDaysLeftToFinish
     */
    public function test_getEstimatedDaysLeftToFinish($status, $expectedDaysToFinish)
    {
        Date::$now = strtotime('2019-03-31');
        $actual = ImportStatus::getEstimatedDaysLeftToFinish($status);
        $this->assertEquals($expectedDaysToFinish, $actual);
    }
    public function getTestDataForGetEstimatedDaysLeftToFinish()
    {
        return [[['main_import_progress' => null, 'import_range_start' => '2013-02-03', 'import_range_end' => '2013-03-05', 'import_start_time' => '2019-03-28', 'idSite' => 1], 'general_Unknown'], [['main_import_progress' => '2013-02-03', 'import_range_start' => '2013-02-03', 'import_range_end' => '2013-03-05', 'import_start_time' => '2019-03-28', 'idSite' => 1], 'general_Unknown'], [['main_import_progress' => '2013-02-15', 'import_range_start' => '2013-02-03', 'import_range_end' => '2013-03-05', 'import_start_time' => '2019-03-28', 'idSite' => 1], 5], [['main_import_progress' => '2013-02-15', 'import_range_start' => '2013-02-03', 'import_range_end' => '', 'import_start_time' => '2019-03-28', 'idSite' => 1], 'general_Unknown']];
    }
    public function test_startingImport_doesNotAllowCreatingMultipleImportsWithTheSameSite()
    {
        $idSite = 1;
        $this->instance->startingImport('testprop', 'testaccount', 'testview', $idSite);
        try {
            $this->instance->startingImport('testprop', 'testaccount', 'testview', $idSite);
            $this->fail('Exception not thrown when trying to start duplicate import.');
        } catch (\Exception $ex) {
            // pass
        }
        $this->instance->finishedImport($idSite);
        $status = $this->getImportStatus($idSite);
        $this->assertEquals(ImportStatus::STATUS_FINISHED, $status['status']);
        $this->instance->startingImport('testprop', 'testaccount', 'testview', $idSite);
        $status = $this->getImportStatus($idSite);
        $this->assertEquals(ImportStatus::STATUS_STARTED, $status['status']);
    }
    public function test_startingImport_doesNotAllowCreatingMultipleImportsWithTheSameSiteGA4()
    {
        $idSite = 1;
        $this->instance->startingImport('prperties/testprop', 'testaccount', '', $idSite, [], 'ga4');
        try {
            $this->instance->startingImport('prperties/testprop', 'testaccount', '', $idSite, [], 'ga4');
            $this->fail('Exception not thrown when trying to start duplicate import.');
        } catch (\Exception $ex) {
            // pass
        }
        $this->instance->finishedImport($idSite);
        $status = $this->getImportStatus($idSite);
        $this->assertEquals(ImportStatus::STATUS_FINISHED, $status['status']);
        $this->instance->startingImport('testprop', 'testaccount', 'testview', $idSite);
        $status = $this->getImportStatus($idSite);
        $this->assertEquals(ImportStatus::STATUS_STARTED, $status['status']);
    }
    public function test_setImportDateRange_throwsIfStartDateIsPastEndDate()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The start date cannot be past the end date.');
        $this->instance->startingImport('p', 'a', 'v', 1);
        $this->instance->setImportDateRange(1, Date::factory('2012-03-04'), Date::factory('2012-01-01'));
    }
    public function test_setImportDateRange_throwsIfStartDateIsPastEndDateGA4()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The start date cannot be past the end date.');
        $this->instance->startingImport('prperties/p', 'a', '', 1, [], 'ga4');
        $this->instance->setImportDateRange(1, Date::factory('2012-03-04'), Date::factory('2012-01-01'));
    }
    public function test_setImportedDateRange_doesNotSetDatesIfTheyAreWithinOverallRange()
    {
        $this->instance->startingImport('p', 'a', 'v', 1);
        $this->instance->setImportDateRange(1, Date::factory('2012-03-04'), Date::factory('2012-03-08'));
        $dateRange = Option::get(ImportStatus::IMPORTED_DATE_RANGE_PREFIX . 1);
        $this->assertEquals(\false, $dateRange);
        $this->instance->setImportedDateRange(1, Date::factory('2012-03-03'), Date::factory('2012-03-08'));
        $dateRange = Option::get(ImportStatus::IMPORTED_DATE_RANGE_PREFIX . 1);
        $this->assertEquals('2012-03-03,2012-03-08', $dateRange);
        $this->instance->setImportedDateRange(1, Date::factory('2012-03-04'), Date::factory('2012-03-07'));
        $dateRange = Option::get(ImportStatus::IMPORTED_DATE_RANGE_PREFIX . 1);
        $this->assertEquals('2012-03-03,2012-03-08', $dateRange);
        $this->instance->setImportedDateRange(1, Date::factory('2012-03-04'), Date::factory('2012-03-17'));
        $dateRange = Option::get(ImportStatus::IMPORTED_DATE_RANGE_PREFIX . 1);
        $this->assertEquals('2012-03-03,2012-03-17', $dateRange);
    }
    public function test_setImportedDateRange_doesNotSetDatesIfTheyAreWithinOverallRangeGA4()
    {
        $this->instance->startingImport('prperties/p', 'a', '', 1, [], 'ga4');
        $this->instance->setImportDateRange(1, Date::factory('2012-03-04'), Date::factory('2012-03-08'));
        $dateRange = Option::get(ImportStatus::IMPORTED_DATE_RANGE_PREFIX . 1);
        $this->assertEquals(\false, $dateRange);
        $this->instance->setImportedDateRange(1, Date::factory('2012-03-03'), Date::factory('2012-03-08'));
        $dateRange = Option::get(ImportStatus::IMPORTED_DATE_RANGE_PREFIX . 1);
        $this->assertEquals('2012-03-03,2012-03-08', $dateRange);
        $this->instance->setImportedDateRange(1, Date::factory('2012-03-04'), Date::factory('2012-03-07'));
        $dateRange = Option::get(ImportStatus::IMPORTED_DATE_RANGE_PREFIX . 1);
        $this->assertEquals('2012-03-03,2012-03-08', $dateRange);
        $this->instance->setImportedDateRange(1, Date::factory('2012-03-04'), Date::factory('2012-03-17'));
        $dateRange = Option::get(ImportStatus::IMPORTED_DATE_RANGE_PREFIX . 1);
        $this->assertEquals('2012-03-03,2012-03-17', $dateRange);
    }
    public function test_setImportedDateRange_setsStartDateToEndDateIfStartDateIsNotSuppliedButEndDateIs()
    {
        $this->instance->startingImport('p', 'a', 'v', 1);
        $dateRange = Option::get(ImportStatus::IMPORTED_DATE_RANGE_PREFIX . 1);
        $this->assertEquals(\false, $dateRange);
        $this->instance->setImportedDateRange(1, null, Date::factory('2012-03-08'));
        $dateRange = Option::get(ImportStatus::IMPORTED_DATE_RANGE_PREFIX . 1);
        $this->assertEquals('2012-03-08,2012-03-08', $dateRange);
    }
    public function test_setImportedDateRange_setsStartDateToEndDateIfStartDateIsNotSuppliedButEndDateIsGA4()
    {
        $this->instance->startingImport('prperties/p', 'a', '', 1, [], 'ga4');
        $dateRange = Option::get(ImportStatus::IMPORTED_DATE_RANGE_PREFIX . 1);
        $this->assertEquals(\false, $dateRange);
        $this->instance->setImportedDateRange(1, null, Date::factory('2012-03-08'));
        $dateRange = Option::get(ImportStatus::IMPORTED_DATE_RANGE_PREFIX . 1);
        $this->assertEquals('2012-03-08,2012-03-08', $dateRange);
    }
    /**
     * @dataProvider getTestDataForGetIsInImportedDateRange
     */
    public function test_isInImportedDateRange_returnsTrueIfRangeIsInSavedImportedDateRange($startDate, $endDate, $period, $date, $expected)
    {
        $this->instance->startingImport('p', 'a', 'v', 1);
        if (!empty($startDate)) {
            $this->instance->setImportedDateRange(1, Date::factory($startDate), Date::factory($endDate));
        }
        $actual = $this->instance->isInImportedDateRange($period, $date, 1);
        $this->assertEquals($expected, $actual);
    }
    public function getTestDataForGetIsInImportedDateRange()
    {
        return [[null, null, 'day', '2013-04-05', \false], ['2013-03-04', '2013-03-24', 'day', '2013-04-05', \false], ['2013-03-04', '2013-03-24', 'day', '2013-03-15', \true], ['2013-03-05', '2013-03-24', 'week', '2013-03-04', \true], ['2013-03-05', '2013-03-24', 'week', '2013-02-04', \false], ['2013-03-04', '2013-03-24', 'month', '2013-02-03', \false], ['2013-03-04', '2013-03-24', 'month', '2013-03-13', \true], ['2013-03-04', '2013-03-24', 'year', '2013-03-13', \true], ['2013-03-04', '2013-03-24', 'year', '2012-03-13', \false]];
    }
    public function test_getAllImportStatuses_returnsAllStatuses()
    {
        Fixture::createWebsite('2012-02-02');
        Fixture::createWebsite('2012-02-02');
        for ($idSite = 4; $idSite < 11; ++$idSite) {
            Fixture::createWebsite('2012-02-02');
            // create a bunch of sites so we have an idSite=10 used
        }
        $this->instance->startingImport('property', 'account', 'view', 1);
        $this->instance->startingImport('property2', 'account2', 'view2', 2);
        $this->instance->startingImport('property3', 'account3', 'view3', 3);
        $this->instance->startingImport('property3', 'account3', 'view3', 10);
        $this->instance->startingImport('properties/1234', 'account', '', 5, [], 'ga4');
        $this->instance->startingImport('properties/879821', 'account2', '', 9, [], 'ga4', ['streamId1', 'streamId2']);
        $statuses = $this->instance->getAllImportStatuses();
        $this->cleanStatuses($statuses);
        $this->assertEquals([['status' => 'started', 'idSite' => 10, 'ga' => ['property' => 'property3', 'account' => 'account3', 'view' => 'view3', 'import_type' => 'Universal Analytics'], 'last_date_imported' => null, 'import_end_time' => null, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [], 'days_finished_since_rate_limit' => 0, 'site' => new Site(10), 'gaInfoPretty' => 'Import Type: Universal Analytics
Property: property3
Account: account3
View: view3', 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \false, 'streamIds' => []], ['status' => 'started', 'idSite' => 9, 'ga' => ['property' => 'properties/879821', 'account' => 'account2', 'view' => '', 'import_type' => 'GA4'], 'last_date_imported' => null, 'import_end_time' => null, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [], 'days_finished_since_rate_limit' => 0, 'site' => new Site(9), 'gaInfoPretty' => 'Import Type: GA4
Property: properties/879821
Account: account2
StreamIds: streamId1, streamId2', 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \true, 'streamIds' => ['streamId1', 'streamId2']], ['status' => 'started', 'idSite' => 5, 'ga' => ['property' => 'properties/1234', 'account' => 'account', 'view' => '', 'import_type' => 'GA4'], 'last_date_imported' => null, 'import_end_time' => null, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [], 'days_finished_since_rate_limit' => 0, 'site' => new Site(5), 'gaInfoPretty' => 'Import Type: GA4
Property: properties/1234
Account: account', 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \true, 'streamIds' => []], ['status' => 'started', 'idSite' => 3, 'ga' => ['property' => 'property3', 'account' => 'account3', 'view' => 'view3', 'import_type' => 'Universal Analytics'], 'last_date_imported' => null, 'import_end_time' => null, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [], 'days_finished_since_rate_limit' => 0, 'site' => new Site(3), 'gaInfoPretty' => 'Import Type: Universal Analytics
Property: property3
Account: account3
View: view3', 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \false, 'streamIds' => []], ['status' => 'started', 'idSite' => 2, 'ga' => ['property' => 'property2', 'account' => 'account2', 'view' => 'view2', 'import_type' => 'Universal Analytics'], 'last_date_imported' => null, 'import_end_time' => null, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [], 'days_finished_since_rate_limit' => 0, 'site' => new Site(2), 'gaInfoPretty' => 'Import Type: Universal Analytics
Property: property2
Account: account2
View: view2', 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \false, 'streamIds' => []], ['status' => 'started', 'idSite' => 1, 'ga' => ['property' => 'property', 'account' => 'account', 'view' => 'view', 'import_type' => 'Universal Analytics'], 'last_date_imported' => null, 'import_end_time' => null, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [], 'days_finished_since_rate_limit' => 0, 'site' => new Site(1), 'gaInfoPretty' => 'Import Type: Universal Analytics
Property: property
Account: account
View: view', 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \false, 'streamIds' => []]], $statuses);
    }
    public function test_getAllImportStatuses_checksKilledStatusIfRequired()
    {
        Date::$now = Date::factory('2015-03-04 00:00:00')->getTimestamp();
        Fixture::createWebsite('2012-02-02');
        Fixture::createWebsite('2012-02-02');
        Fixture::createWebsite('2012-02-02');
        Fixture::createWebsite('2012-02-02');
        Fixture::createWebsite('2012-02-02');
        $status = $this->instance->startingImport('property', 'account', 'view', 1);
        $status['last_job_start_time'] = Date::factory(Date::$now - 500)->getDatetime();
        $this->instance->saveStatus($status);
        $status = $this->instance->startingImport('property2', 'account2', 'view2', 2);
        $status['last_job_start_time'] = Date::factory(Date::$now - 500)->getDatetime();
        $this->instance->saveStatus($status);
        $status = $this->instance->startingImport('property3', 'account3', 'view3', 3);
        $status['last_job_start_time'] = Date::factory(Date::$now - 500)->getDatetime();
        $this->instance->saveStatus($status);
        $status = $this->instance->startingImport('property4', 'account4', 'view4', 4);
        $status['last_job_start_time'] = Date::factory(Date::$now - 500)->getDatetime();
        $this->instance->saveStatus($status);
        $status = $this->instance->startingImport('property5', 'account5', 'view5', 5);
        $status['last_job_start_time'] = Date::factory(Date::$now - 5)->getDatetime();
        $this->instance->saveStatus($status);
        $status = $this->instance->startingImport('properties/6', 'account', '', 6, [], 'ga4');
        $status['last_job_start_time'] = Date::factory(Date::$now - 5)->getDatetime();
        $this->instance->saveStatus($status);
        $lock = ImportReports::makeLock();
        $lock->acquireLock(1);
        $lock2 = ImportReports::makeLock();
        $lock2->acquireLock(2);
        $lock2 = ImportReports::makeLock();
        $lock2->acquireLock(5);
        $this->makeLocksExpired();
        $lock2 = ImportReports::makeLock();
        $lock2->acquireLock(3);
        $lock2 = ImportReports::makeLock();
        $lock2->acquireLock(6);
        $statuses = $this->instance->getAllImportStatuses(\true);
        $this->cleanStatuses($statuses);
        $this->assertEquals([['status' => 'started', 'idSite' => 6, 'ga' => ['property' => 'properties/6', 'account' => 'account', 'view' => '', 'import_type' => 'GA4'], 'last_date_imported' => null, 'import_end_time' => null, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [], 'days_finished_since_rate_limit' => 0, 'site' => new Site(6), 'gaInfoPretty' => 'Import Type: GA4
Property: properties/6
Account: account', 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \true, 'streamIds' => []], ['status' => 'started', 'idSite' => 5, 'ga' => ['property' => 'property5', 'account' => 'account5', 'view' => 'view5', 'import_type' => 'Universal Analytics'], 'last_date_imported' => null, 'import_end_time' => null, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [], 'days_finished_since_rate_limit' => 0, 'site' => new Site(5), 'gaInfoPretty' => 'Import Type: Universal Analytics
Property: property5
Account: account5
View: view5', 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \false, 'streamIds' => []], ['status' => 'killed', 'idSite' => 4, 'ga' => ['property' => 'property4', 'account' => 'account4', 'view' => 'view4', 'import_type' => 'Universal Analytics'], 'last_date_imported' => null, 'import_end_time' => null, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [], 'days_finished_since_rate_limit' => 0, 'site' => new Site(4), 'gaInfoPretty' => 'Import Type: Universal Analytics
Property: property4
Account: account4
View: view4', 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \false, 'streamIds' => []], ['status' => 'started', 'idSite' => 3, 'ga' => ['property' => 'property3', 'account' => 'account3', 'view' => 'view3', 'import_type' => 'Universal Analytics'], 'last_date_imported' => null, 'import_end_time' => null, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [], 'days_finished_since_rate_limit' => 0, 'site' => new Site(3), 'gaInfoPretty' => 'Import Type: Universal Analytics
Property: property3
Account: account3
View: view3', 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \false, 'streamIds' => []], ['status' => 'killed', 'idSite' => 2, 'ga' => ['property' => 'property2', 'account' => 'account2', 'view' => 'view2', 'import_type' => 'Universal Analytics'], 'last_date_imported' => null, 'import_end_time' => null, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [], 'days_finished_since_rate_limit' => 0, 'site' => new Site(2), 'gaInfoPretty' => 'Import Type: Universal Analytics
Property: property2
Account: account2
View: view2', 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \false, 'streamIds' => []], ['status' => 'killed', 'idSite' => 1, 'ga' => ['property' => 'property', 'account' => 'account', 'view' => 'view', 'import_type' => 'Universal Analytics'], 'last_date_imported' => null, 'import_end_time' => null, 'last_day_archived' => null, 'import_range_start' => null, 'import_range_end' => null, 'extra_custom_dimensions' => [], 'days_finished_since_rate_limit' => 0, 'site' => new Site(1), 'gaInfoPretty' => 'Import Type: Universal Analytics
Property: property
Account: account
View: view', 'reimport_ranges' => [], 'main_import_progress' => null, 'isGA4' => \false, 'streamIds' => []]], $statuses);
    }
    public function test_reImportDateRange_throwsIfRangeIsInvalid()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('GoogleAnalyticsImporter_InvalidDateRange');
        $this->instance->reImportDateRange($idSite = 1, Date::factory('2015-02-03'), Date::factory('2015-01-03'));
    }
    public function test_reImportDateRange_addsDateRangeToStatusList()
    {
        $status = $this->instance->startingImport('p', 'a', 'v', $idSite = 1);
        $status['reimport_ranges'] = [['2012-03-04', '2012-04-01'], ['2015-01-04', '2015-02-01'], ['2016-05-05', '2016-05-06']];
        $this->instance->saveStatus($status);
        $this->instance->reImportDateRange($idSite = 1, Date::factory('2017-04-01'), Date::factory('2017-05-01'));
        $this->instance->reImportDateRange($idSite = 1, Date::factory('2016-05-05'), Date::factory('2016-05-06'));
        $status = $this->instance->getImportStatus($idSite = 1);
        $this->assertEquals([['2012-03-04', '2012-04-01'], ['2015-01-04', '2015-02-01'], ['2016-05-05', '2016-05-06'], ['2017-04-01', '2017-05-01'], ['2016-05-05', '2016-05-06']], $status['reimport_ranges']);
    }
    public function test_reImportDateRange_addsDateRangeToStatusListGA4()
    {
        $status = $this->instance->startingImport('properties/p', 'a', '', $idSite = 1, [], 'ga4');
        $status['reimport_ranges'] = [['2012-03-04', '2012-04-01'], ['2015-01-04', '2015-02-01'], ['2016-05-05', '2016-05-06']];
        $this->instance->saveStatus($status);
        $this->instance->reImportDateRange($idSite = 1, Date::factory('2017-04-01'), Date::factory('2017-05-01'));
        $this->instance->reImportDateRange($idSite = 1, Date::factory('2016-05-05'), Date::factory('2016-05-06'));
        $status = $this->instance->getImportStatus($idSite = 1);
        $this->assertEquals([['2012-03-04', '2012-04-01'], ['2015-01-04', '2015-02-01'], ['2016-05-05', '2016-05-06'], ['2017-04-01', '2017-05-01'], ['2016-05-05', '2016-05-06']], $status['reimport_ranges']);
    }
    public function test_reImportDateRange_addsDateRangeToStatusList_ifReimportRangesIsMissng()
    {
        $status = $this->instance->startingImport('p', 'a', 'v', $idSite = 1);
        unset($status['reimport_ranges']);
        $this->instance->saveStatus($status);
        $this->instance->reImportDateRange($idSite = 1, Date::factory('2017-04-01'), Date::factory('2017-05-01'));
        $this->instance->reImportDateRange($idSite = 1, Date::factory('2016-05-05'), Date::factory('2016-05-06'));
        $status = $this->instance->getImportStatus($idSite = 1);
        $this->assertEquals([['2017-04-01', '2017-05-01'], ['2016-05-05', '2016-05-06']], $status['reimport_ranges']);
    }
    public function test_reImportDateRange_addsDateRangeToStatusList_ifReimportRangesIsMissngGA4()
    {
        $status = $this->instance->startingImport('properties/p', 'a', '', $idSite = 1, [], 'ga4');
        unset($status['reimport_ranges']);
        $this->instance->saveStatus($status);
        $this->instance->reImportDateRange($idSite = 1, Date::factory('2017-04-01'), Date::factory('2017-05-01'));
        $this->instance->reImportDateRange($idSite = 1, Date::factory('2016-05-05'), Date::factory('2016-05-06'));
        $status = $this->instance->getImportStatus($idSite = 1);
        $this->assertEquals([['2017-04-01', '2017-05-01'], ['2016-05-05', '2016-05-06']], $status['reimport_ranges']);
    }
    public function test_removeReImportEntry_doesNothingIfReImportListIsEmpty()
    {
        $status = $this->instance->startingImport('p', 'a', 'v', $idSite = 1);
        $status['reimport_ranges'] = [];
        $this->instance->saveStatus($status);
        $this->instance->removeReImportEntry($idSite = 1, ['2016-05-05', '2016-05-06']);
        $status = $this->instance->getImportStatus($idSite = 1);
        $this->assertEmpty($status['reimport_ranges']);
        $status = $this->instance->startingImport('p', 'a', 'v', $idSite = 1);
        unset($status['reimport_ranges']);
        $this->instance->saveStatus($status);
        $this->instance->removeReImportEntry($idSite = 1, ['2016-05-05', '2016-05-06']);
        $status = $this->instance->getImportStatus($idSite = 1);
        $this->assertEmpty($status['reimport_ranges']);
    }
    public function test_removeReImportEntry_doesNothingIfReImportListIsEmptyGA4()
    {
        $status = $this->instance->startingImport('properties/p', 'a', '', $idSite = 1, [], 'ga4');
        $status['reimport_ranges'] = [];
        $this->instance->saveStatus($status);
        $this->instance->removeReImportEntry($idSite = 1, ['2016-05-05', '2016-05-06']);
        $status = $this->instance->getImportStatus($idSite = 1);
        $this->assertEmpty($status['reimport_ranges']);
        $status = $this->instance->startingImport('properties/p', 'a', '', $idSite = 1, [], 'ga4');
        unset($status['reimport_ranges']);
        $this->instance->saveStatus($status);
        $this->instance->removeReImportEntry($idSite = 1, ['2016-05-05', '2016-05-06']);
        $status = $this->instance->getImportStatus($idSite = 1);
        $this->assertEmpty($status['reimport_ranges']);
    }
    public function test_removeReImportEntry_removesAllInstancesOfTheRequestedDateRange()
    {
        $status = $this->instance->startingImport('p', 'a', 'v', $idSite = 1);
        $status['reimport_ranges'] = [['2016-05-05', '2016-05-06'], ['2012-03-04', '2012-04-01'], ['2016-05-05', '2016-05-06'], ['2015-01-04', '2015-02-01'], ['2016-05-05', '2016-05-06'], ['2016-05-04', '2016-05-06']];
        $this->instance->saveStatus($status);
        $this->instance->removeReImportEntry($idSite = 1, ['2016-05-05', '2016-05-06']);
        $status = $this->instance->getImportStatus($idSite = 1);
        $this->assertEquals([['2012-03-04', '2012-04-01'], ['2015-01-04', '2015-02-01'], ['2016-05-04', '2016-05-06']], $status['reimport_ranges']);
    }
    public function test_removeReImportEntry_removesAllInstancesOfTheRequestedDateRangeGA4()
    {
        $status = $this->instance->startingImport('properties/p', 'a', '', $idSite = 1, [], 'ga4');
        $status['reimport_ranges'] = [['2016-05-05', '2016-05-06'], ['2012-03-04', '2012-04-01'], ['2016-05-05', '2016-05-06'], ['2015-01-04', '2015-02-01'], ['2016-05-05', '2016-05-06'], ['2016-05-04', '2016-05-06']];
        $this->instance->saveStatus($status);
        $this->instance->removeReImportEntry($idSite = 1, ['2016-05-05', '2016-05-06']);
        $status = $this->instance->getImportStatus($idSite = 1);
        $this->assertEquals([['2012-03-04', '2012-04-01'], ['2015-01-04', '2015-02-01'], ['2016-05-04', '2016-05-06']], $status['reimport_ranges']);
    }
    public function test_finishImportIfNothingLeft_finishesImportIfProperConditionsMet()
    {
        $status = $this->instance->startingImport('p', 'a', 'v', $idSite = 1);
        $this->assertEquals(ImportStatus::STATUS_STARTED, $status['status']);
        $status['import_range_start'] = '2012-03-04';
        $status['main_import_progress'] = '2012-03-04';
        $this->instance->saveStatus($status);
        $this->instance->finishImportIfNothingLeft($idSite);
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals(ImportStatus::STATUS_FINISHED, $status['status']);
    }
    public function test_finishImportIfNothingLeft_finishesImportIfProperConditionsMetGA4()
    {
        $status = $this->instance->startingImport('properties/p', 'a', '', $idSite = 1, [], 'ga4');
        $this->assertEquals(ImportStatus::STATUS_STARTED, $status['status']);
        $status['import_range_start'] = '2012-03-04';
        $status['main_import_progress'] = '2012-03-04';
        $this->instance->saveStatus($status);
        $this->instance->finishImportIfNothingLeft($idSite);
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals(ImportStatus::STATUS_FINISHED, $status['status']);
    }
    public function test_finishImportIfNothingLeft_finishesImportIfLastDateImportedIsPastStartDate()
    {
        $status = $this->instance->startingImport('p', 'a', 'v', $idSite = 1);
        $this->assertEquals(ImportStatus::STATUS_STARTED, $status['status']);
        $status['import_range_start'] = '2012-03-04';
        $status['main_import_progress'] = '2012-03-03';
        $status['reimport_ranges'] = [];
        $this->instance->saveStatus($status);
        $this->instance->finishImportIfNothingLeft($idSite);
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals(ImportStatus::STATUS_FINISHED, $status['status']);
    }
    public function test_finishImportIfNothingLeft_finishesImportIfLastDateImportedIsPastStartDateGA4()
    {
        $status = $this->instance->startingImport('properties/p', 'a', '', $idSite = 1, [], 'ga4');
        $this->assertEquals(ImportStatus::STATUS_STARTED, $status['status']);
        $status['import_range_start'] = '2012-03-04';
        $status['main_import_progress'] = '2012-03-03';
        $status['reimport_ranges'] = [];
        $this->instance->saveStatus($status);
        $this->instance->finishImportIfNothingLeft($idSite);
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals(ImportStatus::STATUS_FINISHED, $status['status']);
    }
    public function test_finishImportIfNothingLeft_doesNothingIfImportRunsForever()
    {
        $status = $this->instance->startingImport('p', 'a', 'v', $idSite = 1);
        $this->assertEquals(ImportStatus::STATUS_STARTED, $status['status']);
        $status['last_date_imported'] = '2012-03-06';
        $status['main_import_progress'] = '2012-03-06';
        $this->instance->saveStatus($status);
        $this->instance->finishImportIfNothingLeft($idSite);
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals(ImportStatus::STATUS_STARTED, $status['status']);
    }
    public function test_finishImportIfNothingLeft_doesNothingIfImportRunsForeverGA4()
    {
        $status = $this->instance->startingImport('properties/p', 'a', '', $idSite = 1, [], 'ga4');
        $this->assertEquals(ImportStatus::STATUS_STARTED, $status['status']);
        $status['last_date_imported'] = '2012-03-06';
        $status['main_import_progress'] = '2012-03-06';
        $this->instance->saveStatus($status);
        $this->instance->finishImportIfNothingLeft($idSite);
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals(ImportStatus::STATUS_STARTED, $status['status']);
    }
    public function test_finishImportIfNothingLeft_doesNothingIfNothingWasImported()
    {
        $status = $this->instance->startingImport('p', 'a', 'v', $idSite = 1);
        $this->assertEquals(ImportStatus::STATUS_STARTED, $status['status']);
        $status['import_range_start'] = '2012-03-04';
        $this->instance->saveStatus($status);
        $this->instance->finishImportIfNothingLeft($idSite);
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals(ImportStatus::STATUS_STARTED, $status['status']);
    }
    public function test_finishImportIfNothingLeft_doesNothingIfNothingWasImportedGA4()
    {
        $status = $this->instance->startingImport('properties/p', 'a', '', $idSite = 1, [], 'ga4');
        $this->assertEquals(ImportStatus::STATUS_STARTED, $status['status']);
        $status['import_range_start'] = '2012-03-04';
        $this->instance->saveStatus($status);
        $this->instance->finishImportIfNothingLeft($idSite);
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals(ImportStatus::STATUS_STARTED, $status['status']);
    }
    public function test_finishImportIfNothingLeft_doesNothingIfThereAreRangesToReimport()
    {
        $status = $this->instance->startingImport('p', 'a', 'v', $idSite = 1);
        $this->assertEquals(ImportStatus::STATUS_STARTED, $status['status']);
        $status['import_range_start'] = '2012-03-04';
        $status['main_import_progress'] = '2012-03-04';
        $status['reimport_ranges'] = [['2013-04-01', '2013-04-05']];
        $this->instance->saveStatus($status);
        $this->instance->finishImportIfNothingLeft($idSite);
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals(ImportStatus::STATUS_STARTED, $status['status']);
    }
    public function test_finishImportIfNothingLeft_doesNothingIfThereAreRangesToReimportGA4()
    {
        $status = $this->instance->startingImport('properties/p', 'a', '', $idSite = 1, [], 'ga4');
        $this->assertEquals(ImportStatus::STATUS_STARTED, $status['status']);
        $status['import_range_start'] = '2012-03-04';
        $status['main_import_progress'] = '2012-03-04';
        $status['reimport_ranges'] = [['2013-04-01', '2013-04-05']];
        $this->instance->saveStatus($status);
        $this->instance->finishImportIfNothingLeft($idSite);
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals(ImportStatus::STATUS_STARTED, $status['status']);
    }
    public function test_finishImportIfNothingLeft_doesNothingIfLastDateImportedIsBeforeEndDate()
    {
        $status = $this->instance->startingImport('p', 'a', 'v', $idSite = 1);
        $this->assertEquals(ImportStatus::STATUS_STARTED, $status['status']);
        $status['import_range_end'] = '2012-03-04';
        $status['main_import_progress'] = '2012-03-02';
        $status['reimport_ranges'] = [];
        $this->instance->saveStatus($status);
        $this->instance->finishImportIfNothingLeft($idSite);
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals(ImportStatus::STATUS_STARTED, $status['status']);
    }
    public function test_finishImportIfNothingLeft_doesNothingIfLastDateImportedIsBeforeEndDateGA4()
    {
        $status = $this->instance->startingImport('properties/p', 'a', '', $idSite = 1, [], 'ga4');
        $this->assertEquals(ImportStatus::STATUS_STARTED, $status['status']);
        $status['import_range_end'] = '2012-03-04';
        $status['main_import_progress'] = '2012-03-02';
        $status['reimport_ranges'] = [];
        $this->instance->saveStatus($status);
        $this->instance->finishImportIfNothingLeft($idSite);
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals(ImportStatus::STATUS_STARTED, $status['status']);
    }
    public function test_getTotalImportStatusCount_shouldReturnZero()
    {
        $this->assertEquals(0, $this->instance->getTotalImportStatusCount());
    }
    public function test_getTotalImportStatusCount_shouldReturnAllImports()
    {
        $status = $this->instance->startingImport('properties/p', 'a', '', $idSite = 1, [], 'ga4');
        $this->assertEquals(ImportStatus::STATUS_STARTED, $status['status']);
        $status['import_range_end'] = '2012-03-04';
        $status['main_import_progress'] = '2012-03-02';
        $status['reimport_ranges'] = [];
        $this->instance->saveStatus($status);
        $this->instance->finishedImport($idSite);
        $this->assertEquals(1, $this->instance->getTotalImportStatusCount());
        $this->assertEquals(0, $this->instance->getTotalImportStatusCount(\true));
    }
    private function getImportStatus($idSite)
    {
        $optionName = ImportStatus::OPTION_NAME_PREFIX . $idSite;
        Option::clearCachedOption($optionName);
        $data = Option::get($optionName);
        if (empty($data)) {
            return null;
        }
        $data = json_decode($data, \true);
        return $data;
    }
    private function makeLocksExpired()
    {
        Db::query("UPDATE `locks` SET expiry_time = 5");
    }
    private function cleanStatuses(array &$statuses)
    {
        foreach ($statuses as &$status) {
            unset($status['import_start_time']);
            unset($status['last_job_start_time']);
        }
    }
}
