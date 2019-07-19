<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration;


use Piwik\Date;
use Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus;
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
        $this->instance = new ImportStatus();
    }

    public function test_workflow()
    {
        Date::$now = Date::factory('2015-03-04 00:00:00')->getTimestamp();

        $idSite = 5;

        $status = $this->instance->getImportStatus($idSite);
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
        ], $status);

        $this->instance->dayImportFinished($idSite, Date::factory('2015-03-02'));
        $status = $this->instance->getImportStatus($idSite);
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
        ], $status);

        $this->instance->dayImportFinished($idSite, Date::factory('2015-03-04'));
        $this->instance->dayImportFinished($idSite, Date::factory('2015-03-03')); // test it won't set to 03

        $status = $this->instance->getImportStatus($idSite);
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
        ], $status);

        $this->instance->finishedImport($idSite);

        $status = $this->instance->getImportStatus($idSite);
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
        ], $status);
    }

    public function test_error_workflow()
    {
        Date::$now = Date::factory('2015-03-04 00:00:00')->getTimestamp();

        $idSite = 5;

        $status = $this->instance->getImportStatus($idSite);
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
        ], $status);

        $this->instance->erroredImport($idSite, 'test error message');
        $status = $this->instance->getImportStatus($idSite);
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
        ], $status);
    }
}