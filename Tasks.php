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
use Psr\Log\LoggerInterface;

class Tasks extends \Piwik\Plugin\Tasks
{
    public function schedule()
    {
        $this->daily('resumeScheduledImports');
    }

    public function resumeScheduledImports()
    {
        $logger = StaticContainer::get(LoggerInterface::class);

        $importStatus = StaticContainer::get(ImportStatus::class);
        $statuses = $importStatus->getAllImportStatuses();

        foreach ($statuses as $status) {
            if (empty($status['idSite'])
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

    public static function startImport($idSite)
    {
        $hostname = Config::getHostname();

        $importLogFile = 'tmp/logs/gaimportlog.' . $idSite . '.' . $hostname . '.log';

        $command = "nohup php " . PIWIK_INCLUDE_PATH . '/console ';
        if (!empty($hostname)) {
            $command .= '--matomo-domain=' . escapeshellarg($hostname) . ' ';
        }
        $command .= 'googleanalyticsimporter:import-reports --idsite=' . (int)$idSite . ' > ' . $importLogFile . ' 2>&1 &';

        $logger = StaticContainer::get(LoggerInterface::class);
        $logger->debug("Import command: {command}", ['command' => $command]);

        exec($command);
    }
}
