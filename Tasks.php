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

            $hostname = Config::getHostname();

            $command = "nohup php " . PIWIK_INCLUDE_PATH . '/console ';
            if (!empty($hostname)) {
                $command .= '--matomo-domain=' . escapeshellarg($hostname) . ' ';
            }
            $command .= 'googleanalyticsimporter:import-reports --idsite=' . (int)$status['idSite'] . ' > /dev/null 2>&1 &';

            $logger->debug("Import command: {command}", ['command' => $command]);

            exec($command);
        }

        $logger->info('Done scheduling imports.');
    }
}
