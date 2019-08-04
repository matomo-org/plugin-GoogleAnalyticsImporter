<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration;


use Piwik\Date;
use Piwik\Option;
use Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus;
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
            'import_range_start' => null,
            'import_range_end' => null,
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
            'import_range_start' => null,
            'import_range_end' => null,
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
            'import_range_start' => null,
            'import_range_end' => null,
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
        ];
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
}