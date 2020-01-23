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

class ImportStatusTest extends IntegrationTestCase
{
    /**
     * @var ImportStatus
     */
    private $instance;

    public function setUp()
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

        $this->instance->startingImport('property', 'account', 'view', $idSite, [
            ['gaDimension' => 'ga:whatever', 'dimensionScope' => 'visit'],
        ]);
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals([
            'status' => ImportStatus::STATUS_STARTED,
            'idSite' => $idSite,
            'ga' => [
                'property' => 'property',
                'account' => 'account',
                'view' => 'view',
            ],
            'last_date_imported' => null,
            'import_start_time' => Date::$now,
            'import_end_time' => null,
            'last_job_start_time' => Date::$now,
            'last_day_archived' => null,
            'import_range_start' => null,
            'import_range_end' => null,
            'extra_custom_dimensions' => [
                ['gaDimension' => 'ga:whatever', 'dimensionScope' => 'visit'],
            ],
            'days_finished_since_rate_limit' => 0,
            'reimport_ranges' => [],
        ], $status);

        $this->instance->setImportDateRange($idSite, null, null);
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals([
            'status' => ImportStatus::STATUS_STARTED,
            'idSite' => $idSite,
            'ga' => [
                'property' => 'property',
                'account' => 'account',
                'view' => 'view',
            ],
            'last_date_imported' => null,
            'import_start_time' => Date::$now,
            'import_end_time' => null,
            'last_job_start_time' => Date::$now,
            'last_day_archived' => null,
            'import_range_start' => null,
            'import_range_end' => null,
            'extra_custom_dimensions' => [
                ['gaDimension' => 'ga:whatever', 'dimensionScope' => 'visit'],
            ],
            'days_finished_since_rate_limit' => 0,
            'reimport_ranges' => [],
        ], $status);

        $this->instance->setImportDateRange($idSite, Date::factory('2012-03-04'), Date::factory('2012-03-05'));
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals([
            'status' => ImportStatus::STATUS_STARTED,
            'idSite' => $idSite,
            'ga' => [
                'property' => 'property',
                'account' => 'account',
                'view' => 'view',
            ],
            'last_date_imported' => null,
            'import_start_time' => Date::$now,
            'import_end_time' => null,
            'last_job_start_time' => Date::$now,
            'last_day_archived' => null,
            'import_range_start' => '2012-03-04',
            'import_range_end' => '2012-03-05',
            'extra_custom_dimensions' => [
                ['gaDimension' => 'ga:whatever', 'dimensionScope' => 'visit'],
            ],
            'days_finished_since_rate_limit' => 0,
            'reimport_ranges' => [],
        ], $status);

        $this->instance->setImportDateRange($idSite, Date::factory('2017-03-04'), null);
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals([
            'status' => ImportStatus::STATUS_STARTED,
            'idSite' => $idSite,
            'ga' => [
                'property' => 'property',
                'account' => 'account',
                'view' => 'view',
            ],
            'last_date_imported' => null,
            'import_start_time' => Date::$now,
            'import_end_time' => null,
            'last_job_start_time' => Date::$now,
            'last_day_archived' => null,
            'import_range_start' => '2017-03-04',
            'import_range_end' => '',
            'extra_custom_dimensions' => [
                ['gaDimension' => 'ga:whatever', 'dimensionScope' => 'visit'],
            ],
            'days_finished_since_rate_limit' => 0,
            'reimport_ranges' => [],
        ], $status);

        $this->instance->dayImportFinished($idSite, Date::factory('2015-03-02'));
        $status = $this->getImportStatus($idSite);
        $this->assertEquals([
            'status' => ImportStatus::STATUS_ONGOING,
            'idSite' => $idSite,
            'ga' => [
                'property' => 'property',
                'account' => 'account',
                'view' => 'view',
            ],
            'last_date_imported' => '2015-03-02',
            'import_start_time' => Date::$now,
            'import_end_time' => null,
            'last_job_start_time' => Date::$now,
            'last_day_archived' => null,
            'import_range_start' => '2017-03-04',
            'import_range_end' => '',
            'extra_custom_dimensions' => [
                ['gaDimension' => 'ga:whatever', 'dimensionScope' => 'visit'],
            ],
            'days_finished_since_rate_limit' => 1,
            'reimport_ranges' => [],
        ], $status);

        $this->instance->dayImportFinished($idSite, Date::factory('2015-03-04'));
        $this->instance->dayImportFinished($idSite, Date::factory('2015-03-03')); // test it won't set to 03

        $status = $this->getImportStatus($idSite);
        $this->assertEquals([
            'status' => ImportStatus::STATUS_ONGOING,
            'idSite' => $idSite,
            'ga' => [
                'property' => 'property',
                'account' => 'account',
                'view' => 'view',
            ],
            'last_date_imported' => '2015-03-04',
            'import_start_time' => Date::$now,
            'import_end_time' => null,
            'last_job_start_time' => Date::$now,
            'last_day_archived' => null,
            'import_range_start' => '2017-03-04',
            'import_range_end' => '',
            'extra_custom_dimensions' => [
                ['gaDimension' => 'ga:whatever', 'dimensionScope' => 'visit'],
            ],
            'days_finished_since_rate_limit' => 3,
            'reimport_ranges' => [],
        ], $status);

        $this->instance->finishedImport($idSite);

        $status = $this->getImportStatus($idSite);
        $this->assertEquals([
            'status' => ImportStatus::STATUS_FINISHED,
            'idSite' => $idSite,
            'ga' => [
                'property' => 'property',
                'account' => 'account',
                'view' => 'view',
            ],
            'last_date_imported' => '2015-03-04',
            'import_start_time' => Date::$now,
            'import_end_time' => Date::$now,
            'last_job_start_time' => Date::$now,
            'last_day_archived' => null,
            'import_range_start' => '2017-03-04',
            'import_range_end' => '',
            'extra_custom_dimensions' => [
                ['gaDimension' => 'ga:whatever', 'dimensionScope' => 'visit'],
            ],
            'days_finished_since_rate_limit' => 3,
            'reimport_ranges' => [],
        ], $status);

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
        $this->assertEquals([
            'status' => ImportStatus::STATUS_STARTED,
            'idSite' => $idSite,
            'ga' => [
                'property' => 'property',
                'account' => 'account',
                'view' => 'view',
            ],
            'last_date_imported' => null,
            'import_start_time' => Date::$now,
            'import_end_time' => null,
            'last_job_start_time' => Date::$now,
            'last_day_archived' => null,
            'import_range_start' => null,
            'import_range_end' => null,
            'extra_custom_dimensions' => [],
            'days_finished_since_rate_limit' => 0,
            'reimport_ranges' => [],
        ], $status);

        $this->instance->erroredImport($idSite, 'test error message');
        $status = $this->getImportStatus($idSite);
        $this->assertEquals([
            'status' => ImportStatus::STATUS_ERRORED,
            'idSite' => $idSite,
            'ga' => [
                'property' => 'property',
                'account' => 'account',
                'view' => 'view',
            ],
            'last_date_imported' => null,
            'import_start_time' => Date::$now,
            'import_end_time' => null,
            'error' => 'test error message',
            'last_job_start_time' => Date::$now,
            'last_day_archived' => null,
            'import_range_start' => null,
            'import_range_end' => null,
            'extra_custom_dimensions' => [],
            'days_finished_since_rate_limit' => 0,
            'reimport_ranges' => [],
        ], $status);
    }

    public function test_rateLimited_workflow()
    {
        Date::$now = Date::factory('2015-03-04 00:00:00')->getTimestamp();

        $idSite = 5;

        $status = $this->getImportStatus($idSite);
        $this->assertEmpty($status);

        $this->instance->startingImport('property', 'account', 'view', $idSite);
        $status = $this->instance->getImportStatus($idSite);
        $this->assertEquals([
            'status' => ImportStatus::STATUS_STARTED,
            'idSite' => $idSite,
            'ga' => [
                'property' => 'property',
                'account' => 'account',
                'view' => 'view',
            ],
            'last_date_imported' => null,
            'import_start_time' => Date::$now,
            'import_end_time' => null,
            'last_job_start_time' => Date::$now,
            'last_day_archived' => null,
            'import_range_start' => null,
            'import_range_end' => null,
            'extra_custom_dimensions' => [],
            'days_finished_since_rate_limit' => 0,
            'reimport_ranges' => [],
        ], $status);

        $this->instance->rateLimitReached($idSite);
        $status = $this->getImportStatus($idSite);
        $this->assertEquals([
            'status' => ImportStatus::STATUS_RATE_LIMITED,
            'idSite' => $idSite,
            'ga' => [
                'property' => 'property',
                'account' => 'account',
                'view' => 'view',
            ],
            'last_date_imported' => null,
            'import_start_time' => Date::$now,
            'import_end_time' => null,
            'last_job_start_time' => Date::$now,
            'last_day_archived' => null,
            'import_range_start' => null,
            'import_range_end' => null,
            'extra_custom_dimensions' => [],
            'days_finished_since_rate_limit' => 0,
            'reimport_ranges' => [],
        ], $status);
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
        return [
            [
                [
                    'last_date_imported' => null,
                    'import_range_start' => '2013-02-03',
                    'import_range_end' => '2013-03-05',
                    'import_start_time' => '2019-03-28',
                    'idSite' => 1,
                ],
                'general_Unknown',
            ],

            [
                [
                    'last_date_imported' => '2013-02-03',
                    'import_range_start' => '2013-02-03',
                    'import_range_end' => '2013-03-05',
                    'import_start_time' => '2019-03-28',
                    'idSite' => 1,
                ],
                'general_Unknown',
            ],

            [
                [
                    'last_date_imported' => '2013-02-15',
                    'import_range_start' => '2013-02-03',
                    'import_range_end' => '2013-03-05',
                    'import_start_time' => '2019-03-28',
                    'idSite' => 1,
                ],
                5,
            ],

            [
                [
                    'last_date_imported' => '2013-02-15',
                    'import_range_start' => '2013-02-03',
                    'import_range_end' => '',
                    'import_start_time' => '2019-03-28',
                    'idSite' => 1,
                ],
                'general_Unknown',
            ],
        ];
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

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage The start date cannot be past the end date.
     */
    public function test_setImportDateRange_throwsIfStartDateIsPastEndDate()
    {
        $this->instance->startingImport('p', 'a', 'v', 1);
        $this->instance->setImportDateRange(1, Date::factory('2012-03-04'), Date::factory('2012-01-01'));
    }

    public function test_getAllImportStatuses_returnsAllStatuses()
    {
        Fixture::createWebsite('2012-02-02');
        Fixture::createWebsite('2012-02-02');

        $this->instance->startingImport('property', 'account', 'view', 1);
        $this->instance->startingImport('property2', 'account2', 'view2', 2);
        $this->instance->startingImport('property3', 'account3', 'view3', 3);

        $statuses = $this->instance->getAllImportStatuses();
        $this->cleanStatuses($statuses);
        $this->assertEquals([
            [
                'status' => 'started',
                'idSite' => 1,
                'ga' => [
                    'property' => 'property',
                    'account' => 'account',
                    'view' => 'view',
                ],
                'last_date_imported' => null,
                'import_end_time' => null,
                'last_day_archived' => null,
                'import_range_start' => null,
                'import_range_end' => null,
                'extra_custom_dimensions' => [],
                'days_finished_since_rate_limit' => 0,
                'site' => new Site(1),
                'gaInfoPretty' => 'Property: property
Account: account
View: view',
                'reimport_ranges' => [],
            ],
            [
                'status' => 'started',
                'idSite' => 2,
                'ga' => [
                    'property' => 'property2',
                    'account' => 'account2',
                    'view' => 'view2',
                ],
                'last_date_imported' => null,
                'import_end_time' => null,
                'last_day_archived' => null,
                'import_range_start' => null,
                'import_range_end' => null,
                'extra_custom_dimensions' => [],
                'days_finished_since_rate_limit' => 0,
                'site' => new Site(2),
                'gaInfoPretty' => 'Property: property2
Account: account2
View: view2',
                'reimport_ranges' => [],
            ],
            [
                'status' => 'started',
                'idSite' => 3,
                'ga' => [
                    'property' => 'property3',
                    'account' => 'account3',
                    'view' => 'view3',
                ],
                'last_date_imported' => null,
                'import_end_time' => null,
                'last_day_archived' => null,
                'import_range_start' => null,
                'import_range_end' => null,
                'extra_custom_dimensions' => [],
                'days_finished_since_rate_limit' => 0,
                'site' => new Site(3),
                'gaInfoPretty' => 'Property: property3
Account: account3
View: view3',
                'reimport_ranges' => [],
            ],
        ], $statuses);
    }

    public function test_getAllImportStatuses_checksKilledStatusIfRequired()
    {
        Date::$now = Date::factory('2015-03-04 00:00:00')->getTimestamp();

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

        $lock = ImportReports::makeLock();
        $lock->acquireLock(1);

        $lock2 = ImportReports::makeLock();
        $lock2->acquireLock(2);

        $lock2 = ImportReports::makeLock();
        $lock2->acquireLock(5);

        $this->makeLocksExpired();

        $lock2 = ImportReports::makeLock();
        $lock2->acquireLock(3);

        $statuses = $this->instance->getAllImportStatuses(true);
        $this->cleanStatuses($statuses);

        $this->assertEquals([
            [
                'status' => 'killed',
                'idSite' => 1,
                'ga' => [
                    'property' => 'property',
                    'account' => 'account',
                    'view' => 'view',
                ],
                'last_date_imported' => null,
                'import_end_time' => null,
                'last_day_archived' => null,
                'import_range_start' => null,
                'import_range_end' => null,
                'extra_custom_dimensions' => [],
                'days_finished_since_rate_limit' => 0,
                'site' => new Site(1),
                'gaInfoPretty' => 'Property: property
Account: account
View: view',
                'reimport_ranges' => [],
            ],
            [
                'status' => 'killed',
                'idSite' => 2,
                'ga' => [
                    'property' => 'property2',
                    'account' => 'account2',
                    'view' => 'view2',
                ],
                'last_date_imported' => null,
                'import_end_time' => null,
                'last_day_archived' => null,
                'import_range_start' => null,
                'import_range_end' => null,
                'extra_custom_dimensions' => [],
                'days_finished_since_rate_limit' => 0,
                'site' => new Site(2),
                'gaInfoPretty' => 'Property: property2
Account: account2
View: view2',
                'reimport_ranges' => [],
            ],
            [
                'status' => 'started',
                'idSite' => 3,
                'ga' => [
                    'property' => 'property3',
                    'account' => 'account3',
                    'view' => 'view3',
                ],
                'last_date_imported' => null,
                'import_end_time' => null,
                'last_day_archived' => null,
                'import_range_start' => null,
                'import_range_end' => null,
                'extra_custom_dimensions' => [],
                'days_finished_since_rate_limit' => 0,
                'site' => new Site(3),
                'gaInfoPretty' => 'Property: property3
Account: account3
View: view3',
                'reimport_ranges' => [],
            ],
            [
                'status' => 'killed',
                'idSite' => 4,
                'ga' => [
                    'property' => 'property4',
                    'account' => 'account4',
                    'view' => 'view4',
                ],
                'last_date_imported' => null,
                'import_end_time' => null,
                'last_day_archived' => null,
                'import_range_start' => null,
                'import_range_end' => null,
                'extra_custom_dimensions' => [],
                'days_finished_since_rate_limit' => 0,
                'site' => new Site(4),
                'gaInfoPretty' => 'Property: property4
Account: account4
View: view4',
                'reimport_ranges' => [],
            ],
            [
                'status' => 'started',
                'idSite' => 5,
                'ga' => [
                    'property' => 'property5',
                    'account' => 'account5',
                    'view' => 'view5',
                ],
                'last_date_imported' => null,
                'import_end_time' => null,
                'last_day_archived' => null,
                'import_range_start' => null,
                'import_range_end' => null,
                'extra_custom_dimensions' => [],
                'days_finished_since_rate_limit' => 0,
                'site' => new Site(5),
                'gaInfoPretty' => 'Property: property5
Account: account5
View: view5',
                'reimport_ranges' => [],
            ],
        ], $statuses);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage GoogleAnalyticsImporter_InvalidDateRange
     */
    public function test_reImportDateRange_throwsIfRangeIsInvalid()
    {
        $this->instance->reImportDateRange($idSite = 1, Date::factory('2015-02-03'), Date::factory('2015-01-03'));
    }

    public function test_reImportDateRange_addsDateRangeToStatusList()
    {
        $status = $this->instance->startingImport('p', 'a', 'v', $idSite = 1);
        $status['reimport_ranges'] = [
            ['2012-03-04', '2012-04-01'],
            ['2015-01-04', '2015-02-01'],
            ['2016-05-05', '2016-05-06'],
        ];
        $this->instance->saveStatus($status);
        $this->instance->reImportDateRange($idSite = 1, Date::factory('2017-04-01'), Date::factory('2017-05-01'));
        $this->instance->reImportDateRange($idSite = 1, Date::factory('2016-05-05'), Date::factory('2016-05-06'));
        $status = $this->instance->getImportStatus($idSite = 1);
        $this->assertEquals([
            ['2012-03-04', '2012-04-01'],
            ['2015-01-04', '2015-02-01'],
            ['2016-05-05', '2016-05-06'],
            ['2017-04-01', '2017-05-01'],
            ['2016-05-05', '2016-05-06'],
        ], $status['reimport_ranges']);
    }

    public function test_reImportDateRange_addsDateRangeToStatusList_ifReimportRangesIsMissng()
    {
        $status = $this->instance->startingImport('p', 'a', 'v', $idSite = 1);
        unset($status['reimport_ranges']);
        $this->instance->saveStatus($status);

        $this->instance->reImportDateRange($idSite = 1, Date::factory('2017-04-01'), Date::factory('2017-05-01'));
        $this->instance->reImportDateRange($idSite = 1, Date::factory('2016-05-05'), Date::factory('2016-05-06'));

        $status = $this->instance->getImportStatus($idSite = 1);
        $this->assertEquals([
            ['2017-04-01', '2017-05-01'],
            ['2016-05-05', '2016-05-06'],
        ], $status['reimport_ranges']);
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

    public function test_removeReImportEntry_removesAllInstancesOfTheRequestedDateRange()
    {
        $status = $this->instance->startingImport('p', 'a', 'v', $idSite = 1);
        $status['reimport_ranges'] = [
            ['2016-05-05', '2016-05-06'],
            ['2012-03-04', '2012-04-01'],
            ['2016-05-05', '2016-05-06'],
            ['2015-01-04', '2015-02-01'],
            ['2016-05-05', '2016-05-06'],
        ];
        $this->instance->saveStatus($status);

        $this->instance->removeReImportEntry($idSite = 1, ['2016-05-05', '2016-05-06']);
        $status = $this->instance->getImportStatus($idSite = 1);
        $this->assertEquals([
            ['2012-03-04', '2012-04-01'],
            ['2015-01-04', '2015-02-01'],
        ], $status['reimport_ranges']);
    }

    private function getImportStatus($idSite)
    {
        $optionName = ImportStatus::OPTION_NAME_PREFIX . $idSite;
        Option::clearCachedOption($optionName);
        $data = Option::get($optionName);
        if (empty($data)) {
            return null;
        }
        $data = json_decode($data, true);
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