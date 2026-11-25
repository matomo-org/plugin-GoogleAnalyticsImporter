<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\Commands;

use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\AnalyticsAdminServiceClient;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Plugins\GoogleAnalyticsImporter\Commands\ImportGA4Reports;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\AuthorizationGA4;
use Piwik\Plugins\GoogleAnalyticsImporter\ImporterGA4;
use Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group GoogleAnalyticsImporter
 * @group GoogleAnalyticsImporter_Integration
 */
class ImportGA4ReportsTest extends IntegrationTestCase
{
    /**
     * @dataProvider getLeftoverDoNotImportLatestDatesFirstFlag
     */
    public function testResumingFinishedImportWithSingleReimportRangeAndNoDatesOptionImportsReimportRange(bool $doNotImportLatestDatesFirst)
    {
        $idSite = Fixture::createWebsite('2020-01-01 00:00:00');

        $importStatus = StaticContainer::get(ImportStatus::class);
        $importStatus->startingImport('properties/12345', 'account', '', $idSite, [], 'ga4');
        $status = $importStatus->getImportStatus($idSite);
        // the main import has finished and everything up to the import range start was archived
        $status['status'] = ImportStatus::STATUS_ONGOING;
        $status['import_start_time'] = Date::factory('2023-06-01')->getTimestamp();
        $status['import_end_time'] = Date::factory('2023-06-02')->getTimestamp();
        $status['import_range_start'] = '2023-01-01';
        $status['import_range_end'] = '2023-05-31';
        $status['last_day_archived'] = '2023-01-01';
        $status['main_import_progress'] = '2023-01-01';
        $status['reimport_ranges'] = [['2023-03-01', '2023-03-05']];
        // can be left over from an earlier run that imported future dates
        $status['do_not_import_latest_dates_first'] = $doNotImportLatestDatesFirst;
        $importStatus->saveStatus($status);

        $importedRanges = $this->runImportCommand($idSite);

        $this->assertEquals([['2023-03-01', '2023-03-05', false]], $importedRanges);
        $this->assertEquals([], $importStatus->getImportStatus($idSite)['reimport_ranges']);
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
        $idSite = Fixture::createWebsite('2020-01-01 00:00:00');

        $importStatus = StaticContainer::get(ImportStatus::class);
        $importStatus->startingImport('properties/12345', 'account', '', $idSite, [], 'ga4');
        $status = $importStatus->getImportStatus($idSite);
        // the main import is still ongoing and progresses from oldest to latest dates
        $status['status'] = ImportStatus::STATUS_ONGOING;
        $status['import_start_time'] = Date::factory('2023-06-01')->getTimestamp();
        $status['import_end_time'] = null;
        $status['import_range_start'] = '2023-01-01';
        $status['import_range_end'] = '2023-05-31';
        $status['main_import_progress'] = '2023-04-15';
        $status['reimport_ranges'] = [['2023-03-01', '2023-03-05']];
        $status['do_not_import_latest_dates_first'] = true;
        $importStatus->saveStatus($status);

        $importedRanges = $this->runImportCommand($idSite);

        $this->assertEquals([
            ['2023-03-01', '2023-03-05', false],
            ['2023-04-16', '2023-05-31', true],
        ], $importedRanges);
        $this->assertEquals([], $importStatus->getImportStatus($idSite)['reimport_ranges']);
    }

    /**
     * Runs the import command with a mocked importer and returns the ranges passed to ImporterGA4::import(),
     * as [start, end, isDoNotImportLatestDatesFirst].
     */
    private function runImportCommand(int $idSite): array
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

        $commandTester = new CommandTester(new ImportGA4Reports());
        $result = $commandTester->execute([
            '--idsite' => $idSite,
            '--skip-archiving' => true,
        ]);

        $this->assertEquals(0, $result, $commandTester->getDisplay());

        return $importedRanges;
    }
}
