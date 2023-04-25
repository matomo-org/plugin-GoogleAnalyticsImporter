<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\Commands;

use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Option;
use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugin\Manager;
use Piwik\Plugins\GoogleAnalyticsImporter\CannotProcessImportException;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\AuthorizationGA4;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleAnalyticsGA4QueryService;
use Piwik\Site;
use Piwik\Timer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Piwik\Plugins\WebsiteMeasurable\Type;
use Piwik\Plugins\GoogleAnalyticsImporter\ImportConfiguration;
use Piwik\Plugins\GoogleAnalyticsImporter\ImporterGA4;
use Piwik\Plugins\GoogleAnalyticsImporter\ImportLock;
use Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus;
use Piwik\Plugins\GoogleAnalyticsImporter\ImportWasCancelledException;
use Piwik\Plugins\GoogleAnalyticsImporter\Logger\LogToSingleFileProcessor;
use Piwik\Plugins\GoogleAnalyticsImporter\Tasks;

require_once __DIR__ . '/../vendor/autoload.php';


class ImportGA4Reports extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('googleanalyticsimporter:import-ga4-reports');
        $this->setDescription('Import GA4 reports from one or more google analytics properties into Matomo sites.');
        $this->addOption('property', null, InputOption::VALUE_REQUIRED, 'The GA properties to import.');
        $this->addOption('dates', null, InputOption::VALUE_REQUIRED, 'The dates to import, eg, 2015-03-04,2015-04-12.');
        $this->addOption('idsite', null, InputOption::VALUE_REQUIRED, 'The site to import into. This will attempt to continue an existing import.');
        $this->addOption('cvar-count', null, InputOption::VALUE_REQUIRED, 'The number of custom variables to support (if not supplied defaults to however many are currently available). '
            . 'NOTE: This option will attempt to set the number of custom variable slots which should be done with care on an existing system.');
        $this->addOption('skip-archiving', null, InputOption::VALUE_NONE, 'Skips launching archiving at the end of an import. Use this only if executing PHP from the command line results in an error on your system.');
        $this->addOption('mobile-app', null, InputOption::VALUE_NONE, 'If this option is used, the Matomo measurable that is created will be a mobile app. Requires the MobileAppMeasurable be activated.');
        $this->addOption('timezone', null, InputOption::VALUE_REQUIRED, 'If your GA property\'s timezone is set to a value that is not a timezone recognized by PHP, you can specify a valid timezone manually with this option.');
        $this->addOption('extra-custom-dimension', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Map extra google analytics dimensions as matomo dimensions. This can be used to import dimensions like age & gender. Values should be like "gaDimension,dimensionScope", for example "userAgeBracket,visit".', []);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            return $this->executeImpl($input, $output);
        } catch (ImportWasCancelledException $ex) {
            $output->writeln("Import was cancelled, aborting.");
        } catch (CannotProcessImportException $ex) {
            $output->writeln($ex->getMessage());
        }
    }

    protected function executeImpl(InputInterface $input, OutputInterface $output)
    {
        $skipArchiving = $input->getOption('skip-archiving');
        $timezone = $input->getOption('timezone');
        $extraCustomDimensions = $this->getExtraCustomDimensions($input);
        $property = $input->getOption('property');

        $isMobileApp = $input->getOption('mobile-app');
        if ($isMobileApp
            && !Manager::getInstance()->isPluginActivated('MobileAppMeasurable')
        ) {
            throw new \Exception('To create mobile app measurables, please enable the MobileAppMeasurable plugin.');
        }

        $type = $isMobileApp ? \Piwik\Plugins\MobileAppMeasurable\Type::ID : Type::ID;

        $idSite = $this->getIdSite($input);
        LogToSingleFileProcessor::handleLogToSingleFileInCliCommand($idSite, $output);

        /** @var ImportStatus $importStatus */
        $importStatus = StaticContainer::get(ImportStatus::class);

        $canProcessNow = $this->checkIfCanProcess();
        if($canProcessNow['canProcess'] === false){
            $exceededMessage = 'The import was rate limited and will be restarted automatically at ' . $canProcessNow['nextAvailableAt'];
            if (!empty($canProcessNow['rateLimitType']) && $canProcessNow['rateLimitType'] === 'hourly') {
                $importStatus->rateLimitReachedHourly($idSite); //set the error as rate limited, else it leads to error with no message
            } else {
                $importStatus->rateLimitReached($idSite); //set the error as rate limited, else it leads to error with no message
            }
            $output->writeln($exceededMessage);
            throw new CannotProcessImportException($exceededMessage);
        }

        $googleAuth = StaticContainer::get(AuthorizationGA4::class);

        try {
            $gaClient = $googleAuth->getClient();
            $gaAdminClient = $googleAuth->getAdminClient();
        } catch (\Exception $ex) {
            $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "Cannot continue with import, client is misconfigured: " . $ex->getMessage());
            if (!empty($idSite)) {
                $importStatus->erroredImport($idSite, $ex->getMessage());
            }
            return;
        }

        LogToSingleFileProcessor::handleLogToSingleFileInCliCommand($idSite);

        /** @var ImportConfiguration $importerConfiguration */
        $importerConfiguration = StaticContainer::get(ImportConfiguration::class);
        $this->setImportRunConfiguration($importerConfiguration, $input);

        /** @var ImporterGA4 $importer */
        $importer = StaticContainer::get(ImporterGA4::class);
        $importer->setGAClient($gaClient);
        $importer->setGAAdminClient($gaAdminClient);

        $lock = null;

        $shouldFinishImportIfNothingLeft = true;
        $isFutureDateImport = false;

        $createdSiteInCommand = false;
        if (
            empty($idSite) &&
            !empty($property)
        ) {
            try {
                $idSite = $importer->makeSite($property, $timezone, $type, $extraCustomDimensions);
            } catch (\Google\Exception $ex) {
                throw $ex;
            }

            $createdSiteInCommand = true;
            $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "Created new site with ID = $idSite.");
        } else {
            $status = $importStatus->getImportStatus($idSite);
            $isFutureDateImport = (!empty($status['status']) && $status['status'] == ImportStatus::STATUS_FUTURE_DATE_IMPORT_PENDING);
            if (empty($status)) {
                throw new \Exception("There is no ongoing import for site with ID = {$idSite}. Please start a new import.");
            }

            if ($status['status'] == ImportStatus::STATUS_FINISHED) {
                throw new \Exception("The import for site with ID = {$idSite} has finished. Please start a new import.");
            }

            if ($status['status'] == ImportStatus::STATUS_ERRORED) {
                $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "Import for site with ID = $idSite has errored, will attempt to resume.");
            } else {
                $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "Resuming import into existing site $idSite.");
            }

            $property = $status['ga']['property'];
        }
        self::validatePropertyID($property);

        $lock = self::makeLock();
        $success = $lock->acquireLock($idSite);
        if (empty($success)) {
            $n = ceil(ImportLock::getLockTtlConfig(StaticContainer::get(Config::class)) / 60);
            $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "<error>An import is currently in progress. (If the other import has failed, you should be able to try again in about $n minutes.)</error>");
            return;
        }

        $timer = new Timer();

        try {
            $importStatus->resumeImport($idSite);
            $dates = $this->getDatesToImport($input);
            if (empty($dates) && (empty($status['import_end_time']) || $isFutureDateImport)) {
                if (!empty($status['import_range_start'])) {
                    $startDate = Date::factory($status['import_range_start']);
                } else {
                    $startDate = Date::factory(Site::getCreationDateFor($idSite));
                    $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "No dates specified with --dates, importing data from when the GA site was created to today: {$startDate}");
                }

                if (!empty($status['import_range_end'])) {
                    $endDate = Date::factory($status['import_range_end']);
                } else {
                    $endDate = Date::factory('today'); // to ensure continuous import works when no end_date is supplied
                }

                $dates = [$startDate, $endDate];
            } elseif ($createdSiteInCommand
                && !empty($dates)
            ) {
                $importStatus->setImportDateRange($idSite, $dates[0], $dates[1]);
            }

            $abort = $importer->importEntities($idSite, $property);
            if ($abort) {
                $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "Failed to import property entities, aborting.");
                return;
            }

            $dateRangesToReImport = empty($status['reimport_ranges']) ? [] : $status['reimport_ranges'];
            $dateRangesToReImport = array_map(function ($d) {
                return [Date::factory($d[0]), Date::factory($d[1])];
            }, $dateRangesToReImport);

            $dateRangesToImport = $dateRangesToReImport;

            // the range can be invalid if a job is finished, since we'll be at the end date
            if (!empty($dates) &&
                !$dates[1]->isEarlier($dates[0])
            ) {
                $dateRangesToImport = array_merge($dateRangesToReImport, [[$dates[0], $dates[1]]]);
            }

            $dateRangesText = array_map(function ($d) { return $d[0] . ',' . $d[1]; }, $dateRangesToImport);
            $dateRangesText = implode(', ', $dateRangesText);

            $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "Importing the following date ranges in order: " . $dateRangesText);

            // NOTE: date ranges to reimport are handled first, then we go back to the main import (which could be
            // continuous)
            foreach (array_values($dateRangesToImport) as $index => $datesToImport) {
                $status = $importStatus->getImportStatus($idSite); // can change in the meantime, so we refetch

                if (!is_array($datesToImport)
                    || count($datesToImport) != 2
                ) {
                    $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "Found broken entry in date ranges to import (entry #$index) with improper type, skipping.");
                    $importStatus->removeReImportEntry($idSite, $datesToImport);
                    continue;
                }

                $isMainImport = !empty($dates) && empty($status['import_end_time']) && $index == count($dateRangesToImport) - 1; // last is always the main import, if one exists

                list($startDate, $endDate) = $datesToImport;

                if ($isMainImport) {
                    $lastDateImported = !empty($status['main_import_progress']) ? $status['main_import_progress'] : null;
                    $lastDateImported = $lastDateImported ?: (!empty($status['last_date_imported']) ? $status['last_date_imported'] : null);
                } else {
                    $lastDateImported = !empty($status['last_date_imported']) ? $status['last_date_imported'] : null;
                }

                if (!empty($lastDateImported) && $isFutureDateImport) {
                    $startDate = Date::factory($status['future_resume_date'] );
                } else if (!empty($lastDateImported)
                    && Date::factory($lastDateImported)->subDay(1)->isEarlier($endDate)
                ) {
                    $endDate = Date::factory($lastDateImported)->subDay(1);
                }

                if (!$this->isValidDate($startDate)
                    || !$this->isValidDate($endDate)
                ) {
                    $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "Found broken entry in date ranges to import (entry #$index) with invalid date strings, skipping.");
                    $importStatus->removeReImportEntry($idSite, $datesToImport);
                    continue;
                }

                if ($endDate->isEarlier($startDate)) {
                    $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "(Entry #$index) is finished, moving on.");
                    $importStatus->removeReImportEntry($idSite, $datesToImport);
                    continue;
                }

                $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "Importing reports for date range {$startDate} - {$endDate} from GA property $property.");

                try {
                    $importer->setIsMainImport($isMainImport);
                    $aborted = $importer->import($idSite, $property, $startDate, $endDate, $lock);
                    if ($aborted == -1) {
                        $shouldFinishImportIfNothingLeft = false;
                    }
                    if ($aborted) {
                        $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "Error encountered, aborting.");
                        break;
                    }
                } finally {
                    // doing it in the finally since we can get rate limited, which will result in an exception thrown
                    if (!$skipArchiving) {
                        $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "Running archiving for newly imported data...");
                        $status = $importStatus->getImportStatus($idSite);
                        Tasks::startArchive($status, $wait = true, $startDate, $checkImportIsRunning = false);
                    }
                }

                $isReimportEntry = $index < count($dateRangesToReImport);
                if ($isReimportEntry) {
                    $importStatus->removeReImportEntry($idSite, $datesToImport);
                }
            }
        } finally {
            if ($shouldFinishImportIfNothingLeft) {
                $importStatus->finishImportIfNothingLeft($idSite);
            }

            $lock->unlock();
        }

        $queryCount = $importer->getQueryCount();
        $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "Done in $timer. [$queryCount API requests made to GA]");
    }

    private function getExtraCustomDimensions(InputInterface $input)
    {
        $dimensions = $input->getOption('extra-custom-dimension');
        $dimensions = array_map(function ($value) {
            $parts = explode(',', $value);
            if (count($parts) !== 2) {
                throw new \Exception("Invalid --extra-custom-dimension parameter value '$value'.");
            }

            $parts = array_map('trim', $parts);
            return ['ga4Dimension' => $parts[0], 'dimensionScope' => strtolower($parts[1])];
        }, $dimensions);
        return $dimensions;
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

    public static function makeLock()
    {
        return new ImportLock(StaticContainer::get(Config::class));
    }

    private function getDatesToImport(InputInterface $input)
    {
        $dates = $input->getOption('dates');
        if (empty($dates)) {
            return null;
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

    private function isValidDate($date)
    {
        try {
            Date::factory($date);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    public static function validatePropertyID($propertyId)
    {
        if (!preg_match('/^properties\/[^\/]+$/', $propertyId, $matches)) {
            throw new \Exception("Invalid property ID, required format properties/{propertyID}");
        }
    }

    public function checkIfCanProcess()
    {
        $nextAvailableAt = (int) (Option::get(GoogleAnalyticsGA4QueryService::DELAY_OPTION_NAME));
        if (!$nextAvailableAt) {
            return ['canProcess' => true];
        }

        if(Date::factory('now')->getTimestamp() >= $nextAvailableAt){
            Option::delete(GoogleAnalyticsGA4QueryService::DELAY_OPTION_NAME);
            return ['canProcess' => true];
        }
        $rateLimitType = (Date::factory('+1 hour')->getTimestamp() > $nextAvailableAt) ? 'hourly' : 'daily';
        return ['canProcess' => false, 'nextAvailableAt' => Date::factory($nextAvailableAt)->toString('Y-m-d h:i a'), 'rateLimitType' => $rateLimitType];
    }

}
