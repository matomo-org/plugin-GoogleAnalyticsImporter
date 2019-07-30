<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter;

use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Site;
use Psr\Log\LoggerInterface;

class Tasks extends \Piwik\Plugin\Tasks
{
    const DATE_FINISHED_ENV_VAR = 'MATOMO_GOOGLE_IMPORT_END_DATE_TO_ARCHIVE';
    const SECONDS_IN_YEAR = 31557600; // 60 * 60 * 24 * 365.25

    public function schedule()
    {
        $this->daily('resumeScheduledImports');
        $this->daily('archiveImportedReports');
    }

    public function resumeScheduledImports()
    {
        $logger = StaticContainer::get(LoggerInterface::class);

        $importStatus = StaticContainer::get(ImportStatus::class);
        $statuses = $importStatus->getAllImportStatuses();

        foreach ($statuses as $status) {
            if (empty($status['idSite'])
                || !is_numeric($status['idSite'])
                || empty($status['status'])
            ) {
                $logger->info("Found broken import status entry.");
                continue;
            }

            if ($status['status'] == ImportStatus::STATUS_FINISHED) {
                continue;
            }

            if ($status['status'] == ImportStatus::STATUS_ERRORED) {
                $logger->info('Google Analytics import into site with ID = {idSite} encountered an unexpected error last time, attempting to resume.', [
                    'idSite' => $status['idSite'],
                ]);
            } else {
                $logger->info('Resuming import into site with ID = {idSite}.', [
                    'idSite' => $status['idSite'],
                ]);
            }

            self::startImport($status['idSite']);
        }

        $logger->info('Done scheduling imports.');
    }

    public function archiveImportedReports()
    {
        $logger = StaticContainer::get(LoggerInterface::class);

        $importStatus = StaticContainer::get(ImportStatus::class);
        $statuses = $importStatus->getAllImportStatuses();

        foreach ($statuses as $status) {
            $this->startArchive($status);
        }

        $logger->info('Done running archive commands.');
    }

    public static function startImport($idSite)
    {
        $hostname = Config::getHostname();

        // TODO: when deleting an import status, maybe delete the log as well.
        $importLogFile = PIWIK_INCLUDE_PATH . '/tmp/logs/gaimportlog.' . $idSite . '.' . $hostname . '.log';
        if (!is_writable($importLogFile)) {
            $importLogFile = '/dev/null';
        }

        $command = "nohup php " . PIWIK_INCLUDE_PATH . '/console ';
        if (!empty($hostname)) {
            $command .= '--matomo-domain=' . escapeshellarg($hostname) . ' ';
        }
        $command .= 'googleanalyticsimporter:import-reports --idsite=' . (int)$idSite . ' > ' . $importLogFile . ' 2>&1 &';

        $logger = StaticContainer::get(LoggerInterface::class);
        $logger->debug("Import command: {command}", ['command' => $command]);

        exec($command);
    }

    public static function startArchive(array $status, $wait = false)
    {
        $logger = StaticContainer::get(LoggerInterface::class);

        if (empty($status['idSite'])
            || !is_numeric($status['idSite'])
            || empty($status['status'])
        ) {
            $logger->info("Found broken import status entry.");
            return;
        }

        if (empty($status['last_date_imported'])) {
            $logger->info("Import for site ID = {$status['idSite']} has not imported any data yet, skipping archive job.");
            return;
        }

        try {
            $lastDateImported = Date::factory($status['last_date_imported']);
        } catch (\Exception $ex) {
            $logger->info("Found broken import status entry: invalid last imported date '{$status['last_date_imported']}' for site ID = {$status['idSite']}");
            return;
        }

        try {
            $lastDayArchived = empty($status['last_day_archived']) ? null : Date::factory($status['last_day_archived']);
        } catch (\Exception $ex) {
            $logger->info("Found broken import status entry: invalid last day archived date '{$status['last_day_archived']}' for site ID = {$status['idSite']}");
            return;
        }

        if (!empty($lastDayArchived)
            && ($lastDateImported->toString() == $lastDayArchived->toString()
                || $lastDateImported->isEarlier($lastDayArchived))
        ) {
            $logger->debug("Last archived date matches last import date, no need to archive for site ID = {$status['idSite']}");
            return;
        }

        $idSite = (int) $status['idSite'];

        if (empty($lastDayArchived)) {
            $lastDayArchived = Date::factory(Site::getCreationDateFor($idSite));
        }

        $hostname = Config::getHostname();

        $archiveLogFile = PIWIK_INCLUDE_PATH . '/tmp/logs/gaimportlog.archive.' . $idSite . '.' . $hostname . '.log';
        if (!is_writable($archiveLogFile)) {
            $archiveLogFile = '/dev/null';
        }

        $lastN = ceil((Date::today()->getTimestamp() - $lastDayArchived->getTimestamp()) / self::SECONDS_IN_YEAR);
        $lastN = max($lastN, 2);

        $command = self::DATE_FINISHED_ENV_VAR . '=' . $lastDateImported->toString() . " nohup php " . PIWIK_INCLUDE_PATH . '/console ';
        if (!empty($hostname)) {
            $command .= '--matomo-domain=' . escapeshellarg($hostname) . ' ';
        }
        $command .= 'core:archive --force-idsites=' . $idSite . ' --force-periods=week,month,year --force-date-last-n=' . $lastN;

        if (!$wait) {
            $command .= ' > ' . $archiveLogFile . ' 2>&1 &';
        }

        $logger->debug("Archive command for imported site: {command}", ['command' => $command]);

        if ($wait) {
            passthru($command);
        } else {
            exec($command);
        }
    }
}
