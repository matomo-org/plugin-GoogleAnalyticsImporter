<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\System\Commands;

use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\AnalyticsAdminServiceClient;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\AuthorizationGA4;
use Piwik\Plugins\GoogleAnalyticsImporter\ImporterGA4;
use Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\ConsoleCommandTestCase;

/**
 * @group GoogleAnalyticsImporter
 * @group GoogleAnalyticsImporter_System
 * @group Plugins
 */
class ImportGA4ReportsTest extends ConsoleCommandTestCase
{
    /**
     * @dataProvider getLeftoverDoNotImportLatestDatesFirstFlag
     */
    public function testResumingFinishedImportWithSingleReimportRangeAndNoDatesOptionImportsReimportRange(bool $doNotImportLatestDatesFirst)
    {
        $idSite = $this->createImportWithStatus([
            // the main import has finished and everything up to the import range start was archived
            'import_end_time' => Date::factory('2023-06-02')->getTimestamp(),
            'last_day_archived' => '2023-01-01',
            'main_import_progress' => '2023-01-01',
            // can be left over from an earlier run that imported future dates
            'do_not_import_latest_dates_first' => $doNotImportLatestDatesFirst,
        ]);

        $importedRanges = $this->runImportCommand($idSite);

        $this->assertEquals([['2023-03-01', '2023-03-05', false]], $importedRanges);
        $this->assertEquals([], $this->getImportStatus($idSite)['reimport_ranges']);
    }

    public function getLeftoverDoNotImportLatestDatesFirstFlag(): array
    {
        return [
            'flag not set' => [false],
            'flag left set from an earlier run' => [true],
        ];
    }

    public function testResumingOngoingImportWithReimportRangeKeepsDoNotImportLatestDatesFirstForMainRange()
    {
        $idSite = $this->createImportWithStatus([
            // the main import is still ongoing and progresses from oldest to latest dates
            'main_import_progress' => '2023-04-15',
            'do_not_import_latest_dates_first' => true,
        ]);

        $importedRanges = $this->runImportCommand($idSite);

        $this->assertEquals([
            ['2023-03-01', '2023-03-05', false],
            ['2023-04-16', '2023-05-31', true],
        ], $importedRanges);
        $this->assertEquals([], $this->getImportStatus($idSite)['reimport_ranges']);
    }

    public function testResumingWithInvalidDatesOptionAndSingleReimportRangeImportsReimportRangeUnchanged()
    {
        $idSite = $this->createImportWithStatus([
            'import_end_time' => Date::factory('2023-06-02')->getTimestamp(),
            'last_day_archived' => '2023-01-01',
        ]);

        // the end is before the start, so only the reimport range is imported
        $dates = Date::factory('today')->addDay(10)->toString() . ',' . Date::factory('today')->addDay(5)->toString();
        $importedRanges = $this->runImportCommand($idSite, ['--dates' => $dates]);

        $this->assertEquals([['2023-03-01', '2023-03-05', false]], $importedRanges);
        $status = $this->getImportStatus($idSite);
        $this->assertEquals([], $status['reimport_ranges']);
        $this->assertEmpty($status['do_not_import_latest_dates_first'] ?? null);
    }

    private function createImportWithStatus(array $statusOverrides): int
    {
        $idSite = Fixture::createWebsite('2020-01-01 00:00:00');

        $importStatus = StaticContainer::get(ImportStatus::class);
        $importStatus->startingImport('properties/12345', 'account', '', $idSite, [], 'ga4');
        $status = array_merge($importStatus->getImportStatus($idSite), [
            'status' => ImportStatus::STATUS_ONGOING,
            'import_start_time' => Date::factory('2023-06-01')->getTimestamp(),
            'import_end_time' => null,
            'import_range_start' => '2023-01-01',
            'import_range_end' => '2023-05-31',
            'reimport_ranges' => [['2023-03-01', '2023-03-05']],
        ], $statusOverrides);
        $importStatus->saveStatus($status);

        return $idSite;
    }

    private function getImportStatus(int $idSite): array
    {
        return StaticContainer::get(ImportStatus::class)->getImportStatus($idSite);
    }

    /**
     * Runs the import command with a mocked importer and returns the ranges passed to ImporterGA4::import(),
     * as [start, end, isDoNotImportLatestDatesFirst].
     */
    private function runImportCommand(int $idSite, array $options = []): array
    {
        $authorization = $this->createMock(AuthorizationGA4::class);
        $authorization->method('getClient')->willReturn($this->createMock(BetaAnalyticsDataClient::class));
        $authorization->method('getAdminClient')->willReturn($this->createMock(AnalyticsAdminServiceClient::class));

        $importedRanges = [];
        $importer = $this->createMock(ImporterGA4::class);
        $importer->method('importEntities')->willReturn(false);
        $importer->method('getQueryCount')->willReturn(0);
        $importer->method('import')->willReturnCallback(
            function ($idSite, $propertyId, Date $start, Date $end, $lock, $segment, $streamIds, $isDoNotImportLatestDatesFirst) use (&$importedRanges) {
                $importedRanges[] = [$start->toString(), $end->toString(), $isDoNotImportLatestDatesFirst];
                return false;
            }
        );

        StaticContainer::getContainer()->set(AuthorizationGA4::class, $authorization);
        StaticContainer::getContainer()->set(ImporterGA4::class, $importer);

        $result = $this->applicationTester->run(array_merge([
            'command' => 'googleanalyticsimporter:import-ga4-reports',
            '--idsite' => $idSite,
            '--skip-archiving' => true,
        ], $options));

        $this->assertEquals(0, $result, $this->getCommandDisplayOutputErrorMessage());

        return $importedRanges;
    }
}

ImportGA4ReportsTest::$fixture = new Fixture();
