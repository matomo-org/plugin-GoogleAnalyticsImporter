<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\Commands;

use Piwik\Concurrency\Lock;
use Piwik\Concurrency\LockBackend\MySqlLockBackend;
use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\Authorization;
use Piwik\Plugins\GoogleAnalyticsImporter\ImportConfiguration;
use Piwik\Plugins\GoogleAnalyticsImporter\Importer;
use Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus;
use Piwik\Site;
use Piwik\Timer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

require_once PIWIK_INCLUDE_PATH . '/plugins/GoogleAnalyticsImporter/vendor/autoload.php';

// TODO: make sure same version of google api client is used in this & SearchEngineKeywordsPerformance
// (may have to add test in target plugin)
// TODO: support importing segments
class ImportReports extends ConsoleCommand
{
    const IMPORT_LOCK_NAME = 'GoogleAnalyticsImport_importLock';

    protected function configure()
    {
        $this->setName('googleanalyticsimporter:import-reports');
        $this->setDescription('Import reports from one or more google analytics properties into Matomo sites.');
        $this->addOption('property', null, InputOption::VALUE_REQUIRED, 'The GA properties to import.');
        $this->addOption('account', null, InputOption::VALUE_REQUIRED, 'The account ID to get views from.');
        $this->addOption('view', null, InputOption::VALUE_REQUIRED, 'The View ID to use. If not supplied, the default View for the property is used.');
        $this->addOption('dates', null, InputOption::VALUE_REQUIRED, 'The dates to import.');
        $this->addOption('idsite', null, InputOption::VALUE_REQUIRED, 'The site to import into. This will attempt to continue an existing import.');
        $this->addOption('cvar-count', null, InputOption::VALUE_REQUIRED, 'The number of custom variables to support (if not supplied defaults to however many are currently available). '
            . 'NOTE: This option will attempt to set the number of custom variable slots which should be done with care on an existing system.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $googleAuth = StaticContainer::get(Authorization::class);
        $googleClient = $googleAuth->getConfiguredClient();

        $service = new \Google_Service_Analytics($googleClient);

        $idSite = $this->getIdSite($input);
        if (empty($idSite)) {
            $viewId = $this->getViewId($input, $output, $service);
            $property = $input->getOption('property');

            $account = $input->getOption('account');
            if (empty($account)) {
                $account = self::guessAccountFromProperty($property);
            }
        }

        /** @var ImportConfiguration $importerConfiguration */
        $importerConfiguration = StaticContainer::get(ImportConfiguration::class);
        $this->setImportRunConfiguration($importerConfiguration, $input);

        /** @var Importer $importer */
        $importer = StaticContainer::get(Importer::class);

        /** @var ImportStatus $importStatus */
        $importStatus = StaticContainer::get(ImportStatus::class);

        $lock = null;

        if (empty($idSite)
            && !empty($property)
            && !empty($account)
        ) {
            $idSite = $importer->makeSite($account, $property, $viewId);
            $output->writeln("Created new site with ID = $idSite.");
        } else {
            $status = $importStatus->getImportStatus($idSite);
            if (empty($status)) {
                throw new \Exception("There is no ongoing import for site with ID = {$idSite}. Please start a new import.");
            }

            if ($status['status'] == ImportStatus::STATUS_FINISHED) {
                throw new \Exception("The import for site with ID = {$idSite} has finished. Please start a new import.");
            }

            if (!empty($status['last_date_imported'])) {
                $dates = [Date::factory($status['last_date_imported'])->addDay(1), Date::factory('today')];
            }

            if ($status['status'] == ImportStatus::STATUS_ERRORED) {
                $output->writeln("Import for site with ID = $idSite has errored, will attempt to resume.");
            } else {
                $output->writeln("Resuming import into existing site $idSite.");
            }

            $account = $status['ga']['account'];
            $property = $status['ga']['property'];
            $viewId = $status['ga']['view'];
        }

        $lock = $this->makeLock();
        $success = $lock->acquireLock($idSite, Importer::LOCK_TTL);
        if (empty($success)) {
            throw new \Exception("An import is currently in progress. (If the other import has failed, you should be able to try again in about 5 minutes.)");
        }

        try {
            $importStatus->resumeImport($idSite);

            if (empty($dates)) {
                $dates = $this->getDatesToImport($input, $output, $service, $account, $property);
            }

            $importer->importEntities($idSite, $account, $property, $viewId);

            $output->writeln("Importing reports for date range {$dates[0]} - {$dates[1]} from GA view $viewId.");

            $timer = new Timer();

            $importer->import($idSite, $viewId, $dates[0], $dates[1], $lock);

            $queryCount = $importer->getQueryCount();
            $output->writeln("Done in $timer. [$queryCount API requests made to GA]");
        } finally {
            $lock->unlock();
        }
    }

    private function getViewId(InputInterface $input, OutputInterface $output, \Google_Service_Analytics $service)
    {
        $viewId = $input->getOption('view');
        if (!empty($viewId)) {
            return $viewId;
        }

        $propertyId = $input->getOption('property');
        $accountId = $input->getOption('account');
        if (empty($propertyId)
            && empty($accountId)
        ) {
            throw new \Exception("Either a single --view or both --property and --account must be supplied.");
        }

        $profiles = $service->management_profiles->listManagementProfiles($accountId, $propertyId);

        /** @var \Google_Service_Analytics_Profile $profile */
        $profile = reset($profiles->getItems());
        $profileId = $profile->id;

        $output->writeln("No view ID supplied, using first profile in the supplied account/property: " . $profileId);

        return $profileId;
    }

    private function getDatesToImport(InputInterface $input, OutputInterface $output, \Google_Service_Analytics $service, $account, $property)
    {
        $dates = $input->getOption('dates');
        if (empty($dates)) {
            $webProperty = $service->management_webproperties->get($account, $property);
            $dates = Date::factory($webProperty->getCreated())->toString() . ',' . 'today';
            $output->writeln("No dates specified with --dates, importing data from when the GA site was created to today: $dates");
        }

        $dates = explode(',', $dates);

        if (count($dates) != 2) {
            $this->invalidDatesOption();
        }

        return [
            $this->parseDate($dates[0]),
            $this->parseDate($dates[1]),
        ];
    }

    private function invalidDatesOption()
    {
        throw new \Exception("Invalid value for the dates option supplied, must be a comma separated value with two "
            . "dates, eg, 2014-02-03,2015-02-03");
    }

    private function parseDate($date)
    {
        try {
            return Date::factory($date);
        } catch (\Exception $ex) {
            return $this->invalidDatesOption();
        }
    }

    private function getIdSite(InputInterface $input)
    {
        $idSite = $input->getOption('idsite');
        if (!empty($idSite)) {
            if (!is_numeric($idSite)) {
                throw new \Exception("Invalid --idsite value provided, must be an integer.");
            }

            try {
                new Site($idSite);
            } catch (\Exception $ex) {
                throw new \Exception("Site ID $idSite does not exist.");
            }
        }
        return $idSite;
    }

    private function setImportRunConfiguration(ImportConfiguration $importerConfiguration, InputInterface $input)
    {
        $cvarCount = (int) $input->getOption('cvar-count');
        $importerConfiguration->setNumCustomVariables($cvarCount);
    }

    public static function guessAccountFromProperty($property)
    {
        if (!preg_match('/UA-(\d+)-\d/', $property, $matches)) {
            throw new \Exception("Cannot deduce account ID from property ID '$property'. Please specify it manually using the --account option.");
        }

        return $matches[1];
    }

    private function makeLock()
    {
        return new Lock(new MySqlLockBackend(), self::IMPORT_LOCK_NAME);
    }
}
