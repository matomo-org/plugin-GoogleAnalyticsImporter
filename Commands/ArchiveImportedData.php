<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\Commands;

use Piwik\Container\StaticContainer;
use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus;
use Piwik\Plugins\GoogleAnalyticsImporter\Logger\LogToSingleFileProcessor;
use Piwik\Plugins\GoogleAnalyticsImporter\Tasks;
class ArchiveImportedData extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('googleanalyticsimporter:archive-imported-data');
        $this->setDescription('Initiates core:archive for an imported site. This is run automatically every day, but can be run manually if needed.' . ' All it really does is call core:archive w/ a few custom parameters so data from years back gets archived.');
        $this->addRequiredValueOption('idSite', null, 'The ID of the imported site to initiate archiving for.');
    }
    /**
     * @return int
     */
    protected function doExecute() : int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        $idSite = (int) $input->getOption('idSite');
        LogToSingleFileProcessor::handleLogToSingleFileInCliCommand($idSite);
        $importStatus = StaticContainer::get(ImportStatus::class);
        try {
            $status = $importStatus->getImportStatus($idSite);
        } catch (\Exception $ex) {
            $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "No import found for site ID = {$idSite}.");
            return self::FAILURE;
        }
        $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "Starting core:archive for site ID = {$idSite}.");
        Tasks::startArchive($status, $wait = \true);
        $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "Done.");
        return self::SUCCESS;
    }
}
