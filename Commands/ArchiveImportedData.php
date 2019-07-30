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
use Piwik\Plugins\GoogleAnalyticsImporter\Tasks;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ArchiveImportedData extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('googleanalyticsimporter:archive-imported-data');
        $this->setDescription('Initiates core:archive for an imported site. This is run automatically every day, but can be run manually if needed.'
            . ' All it really does is call core:archive w/ a few custom parameters so data from years back gets archived.');
        $this->addOption('idSite', null, InputOption::VALUE_REQUIRED, 'The ID of the imported site to initiate archiving for.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $idSite = (int) $input->getOption('idSite');

        $importStatus = StaticContainer::get(ImportStatus::class);

        try {
            $status = $importStatus->getImportStatus($idSite);
        } catch (\Exception $ex) {
            $output->writeln("No import found for site ID = $idSite.");
            return;
        }

        $output->writeln("Starting core:archive for site ID = $idSite.");

        Tasks::startArchive($status, $wait = true);

        $output->writeln("Done.");
    }
}
