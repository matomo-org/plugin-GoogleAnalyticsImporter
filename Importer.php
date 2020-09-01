<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter;

use Google_Service_Analytics_Goal;
use Piwik\API\Request;
use Piwik\Archive\ArchiveInvalidator;
use Piwik\ArchiveProcessor\Parameters;
use Piwik\Common;
use Piwik\Concurrency\Lock;
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\DataAccess\ArchiveWriter;
use Piwik\Date;
use Piwik\Db;
use Piwik\Option;
use Piwik\Period\Factory;
use Piwik\Piwik;
use Piwik\Plugin\Manager;
use Piwik\Plugin\ReportsProvider;
use Piwik\Plugins\Goals\API;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\DailyRateLimitReached;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleAnalyticsQueryService;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleCustomDimensionMapper;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleGoalMapper;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleQueryObjectFactory;
use Piwik\Plugins\GoogleAnalyticsImporter\Input\EndDate;
use Piwik\Plugins\GoogleAnalyticsImporter\Input\MaxEndDateReached;
use Piwik\Plugins\SitesManager\API as SitesManagerAPI;
use Piwik\Plugins\Goals\API as GoalsAPI;
use Piwik\Plugins\CustomDimensions\API as CustomDimensionsAPI;
use Piwik\Plugins\WebsiteMeasurable\Type;
use Piwik\Segment;
use Piwik\SettingsPiwik;
use Piwik\SettingsServer;
use Piwik\Site;
use Psr\Log\LoggerInterface;

class Importer
{
    const IS_IMPORTED_FROM_GA_NUMERIC = 'GoogleAnalyticsImporter_isImportedFromGa';

    /**
     * @var ReportsProvider
     */
    private $reportsProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \Google_Service_Analytics
     */
    private $gaService;

    /**
     * @var \Google_Service_AnalyticsReporting
     */
    private $gaServiceReporting;

    /**
     * @var array|null
     */
    private $recordImporters;

    /**
     * @var GoogleGoalMapper
     */
    private $goalMapper;

    /**
     * @var GoogleCustomDimensionMapper
     */
    private $customDimensionMapper;

    /**
     * @var IdMapper
     */
    private $idMapper;

    /**
     * @var int
     */
    private $queryCount = 0;

    /**
     * @var ImportStatus
     */
    private $importStatus;

    /**
     * @var Lock
     */
    private $currentLock = null;

    /**
     * @var string
     */
    private $noDataMessageRemoved = false;

    /**
     * @var ArchiveInvalidator
     */
    private $invalidator;

    /**
     * @var EndDate
     */
    private $endDate;

    /**
     * Whether this is the main import date range or for a reimport range.
     * @var bool
     */
    private $isMainImport = true;

    public function __construct(ReportsProvider $reportsProvider, \Google_Service_Analytics $gaService, \Google_Service_AnalyticsReporting $gaReportingService,
                                LoggerInterface $logger, GoogleGoalMapper $goalMapper, GoogleCustomDimensionMapper $customDimensionMapper,
                                IdMapper $idMapper, ImportStatus $importStatus, ArchiveInvalidator $invalidator, EndDate $endDate)
    {
        $this->reportsProvider = $reportsProvider;
        $this->gaService = $gaService;
        $this->gaServiceReporting = $gaReportingService;
        $this->logger = $logger;
        $this->goalMapper = $goalMapper;
        $this->customDimensionMapper = $customDimensionMapper;
        $this->idMapper = $idMapper;
        $this->importStatus = $importStatus;
        $this->invalidator = $invalidator;
        $this->endDate = $endDate;
    }

    public function setIsMainImport($isMainImport)
    {
        $this->isMainImport = $isMainImport;
    }

    public function makeSite($accountId, $propertyId, $viewId, $timezone = false, $type = Type::ID, $extraCustomDimensions = [], $forceCustomDimensionSlotCheck = false)
    {
        $extraCustomDimensions = $this->checkExtraCustomDimensions($extraCustomDimensions);

        $webproperty = $this->gaService->management_webproperties->get($accountId, $propertyId);
        $view = $this->gaService->management_profiles->get($accountId, $propertyId, $viewId);

        $startDate = Date::factory($webproperty->getCreated())->toString();
        if (!method_exists(SettingsServer::class, 'isMatomoForWordPress') || !SettingsServer::isMatomoForWordPress()) {
            $idSite = SitesManagerAPI::getInstance()->addSite(
                $siteName = $webproperty->getName(),
                $urls = $type === \Piwik\Plugins\MobileAppMeasurable\Type::ID ? null : [$webproperty->getWebsiteUrl()],
                $ecommerce = $view->eCommerceTracking ? 1 : 0,
                $siteSearch = !empty($view->siteSearchQueryParameters),
                $searchKeywordParams = $view->siteSearchQueryParameters,
                $searchCategoryParams = $view->siteSearchCategoryParameters,
                $excludedIps = null,
                $excludedParams = $view->excludeQueryParameters,
                $timezone = empty($timezone) ? $view->timezone : $timezone,
                $currency = $view->currency,
                $group = null,
                $startDate,
                $excludedUserAgents = null,
                $keepURLFragments = null,
                $type
            );
        } else { // matomo for wordpress
            $site = new \WpMatomo\Site();
            $idSite = $site->get_current_matomo_site_id();

            $creationTime = Date::factory(Site::getCreationDateFor($idSite));
            if ($creationTime->isLater(Date::factory($startDate))) {
                // manually set the website creation date to a day earlier than the earliest day we import
                Db::get()->update(Common::prefixTable("site"),
                    ['ts_created' => $startDate],
                    "idsite = $idSite"
                );
            }
        }

        if ($forceCustomDimensionSlotCheck) {
            $availableScopes = CustomDimensionsAPI::getInstance()->getAvailableScopes($idSite);
            $customDimensions = $this->gaService->management_customDimensions->listManagementCustomDimensions($accountId, $propertyId);

            $this->customDimensionMapper->checkCustomDimensionCount($availableScopes, $customDimensions, $extraCustomDimensions, $idSite, $accountId, $propertyId);
        }

        $this->importStatus->startingImport($propertyId, $accountId, $viewId, $idSite, $extraCustomDimensions);

        return $idSite;
    }

    public function importEntities($idSite, $accountId, $propertyId, $viewId)
    {
        try {
            $this->importGoals($idSite, $accountId, $propertyId, $viewId);
            $this->importCustomDimensions($idSite, $accountId, $propertyId);
            $this->importCustomVariableSlots();
        } catch (\Exception $ex) {
            $this->onError($idSite, $ex);
            return true;
        }
    }

    private function importGoals($idSite, $accountId, $propertyId, $viewId)
    {
        if ($this->isPluginUnavailable('Goals')) {
            $this->logger->warning("Goals plugin is not activated or present, skipping goal import.");
            return;
        }

        $existingGoals = API::getInstance()->getGoals($idSite);

        $goals = $this->gaService->management_goals->listManagementGoals($accountId, $propertyId, $viewId);

        /** @var Google_Service_Analytics_Goal $gaGoal */
        foreach ($goals->getItems() as $gaGoal) {
            if ($this->goalExists($existingGoals, $gaGoal)) {
                $this->logger->info("Goal '{gaGoalName}' already imported.", [
                    'gaGoalName' => $gaGoal->getName(),
                ]);
                continue;
            }

            try {
                $goal = $this->goalMapper->map($gaGoal, $idSite);
            } catch (CannotImportGoalException $ex) {
                $this->logger->warning($ex->getMessage());
                $this->logger->warning('Importing this goal as a manually triggered goal. Metrics for this goal will be available, but tracking will not work for this goal in Matomo.');

                $goal = $this->goalMapper->mapManualGoal($gaGoal);
            }

            $idGoal = Request::processRequest('Goals.addGoal', [
                'idSite' => $idSite,
                'name' => $gaGoal->getName(),
                'matchAttribute' => $goal['match_attribute'],
                'pattern' => $goal['pattern'],
                'patternType' => $goal['pattern_type'],
                'caseSensitive' => $goal['case_sensitive'],
                'revenue' => $goal['revenue'],
                'allowMultipleConversionsPerVisit' => $goal['allow_multiple_conversions'],
                'description' => $goal['description'],
                'useEventValueAsRevenue' => $goal['use_event_value_as_revenue'],
            ], $default = []);

            $this->idMapper->mapEntityId('goal', $gaGoal->getId(), $idGoal, $idSite);

            if (!empty($goal['funnel'])) {
                StaticContainer::get(\Piwik\Plugins\Funnels\Model\FunnelsModel::class)->clearGoalsCache();
                \Piwik\Plugins\Funnels\API::getInstance()->setGoalFunnel($idSite, $idGoal, true, $goal['funnel']);
            }
        }
    }

    private function importCustomDimensions($idSite, $accountId, $propertyId)
    {
        if ($this->isPluginUnavailable('CustomDimensions')) {
            $this->logger->warning("The CustomDimensions plugin is not activated or present, skipping custom dimension import.");
            return;
        }

        $existingCustomDimensions = \Piwik\Plugins\CustomDimensions\API::getInstance()->getConfiguredCustomDimensions($idSite);
        $customDimensions = $this->gaService->management_customDimensions->listManagementCustomDimensions($accountId, $propertyId);

        /** @var \Google_Service_Analytics_CustomDimension $gaCustomDimension */
        foreach ($customDimensions->getItems() as $gaCustomDimension) {
            if (!preg_match('/ga:dimension([0-9]+)/', $gaCustomDimension->getId(), $matches)) {
                $this->logger->warning("Could not parse custom dimension ID from GA: {$gaCustomDimension->getId()}");
                continue;
            }

            if ($this->customDimensionExists($existingCustomDimensions, $gaCustomDimension)) {
                $this->logger->info("Custom Dimension '{gaCustomDimension}' already imported.", [
                    'gaCustomDimension' => $gaCustomDimension->getName(),
                ]);
                continue;
            }

            $gaId = $matches[1];

            try {
                $customDimension = $this->customDimensionMapper->map($gaCustomDimension);
            } catch (CannotImportCustomDimensionException $ex) {
                $this->logger->warning($ex->getMessage());
                $this->logger->warning("Skipping this custom dimension.");
                continue;
            }

            try {
                $idDimension = CustomDimensionsAPI::getInstance()->configureNewCustomDimension(
                    $idSite, $customDimension['name'], $customDimension['scope'], $customDimension['active'], $customDimension['extractions'],
                    $customDimension['case_sensitive']);
            } catch (\Exception $ex) {
                if (strpos($ex->getMessage(), 'All Custom Dimensions for website') === 0) {
                    $this->logger->warning("Cannot map custom dimension {$customDimension['name']}: " . $ex->getMessage());
                    continue;
                }
            }

            $this->idMapper->mapEntityId('customdimension', $gaId, $idDimension, $idSite);
        }

        // create extra custom dimensions
        $importStatus = $this->importStatus->getImportStatus($idSite);
        $extraCustomDimensions = !empty($importStatus['extra_custom_dimensions']) ? $importStatus['extra_custom_dimensions'] : [];
        foreach ($extraCustomDimensions as $extraEntry) {
            if ($this->extraCustomDimensionExists($existingCustomDimensions, $extraEntry['gaDimension'])) {
                $this->logger->info("Extra custom dimension '{gaCustomDimension}' entity already imported.", [
                    'gaCustomDimension' => $extraEntry['gaDimension'],
                ]);
                continue;
            }

            $idDimension = CustomDimensionsAPI::getInstance()->configureNewCustomDimension(
                $idSite, $extraEntry['gaDimension'], $extraEntry['dimensionScope'], $active = true);

            $this->logger->info("Created Matomo dimension for extra dimension {gaDim} as dimension{id} with scope '{scope}'.", [
                'gaDim' => $extraEntry['gaDimension'],
                'id' => $idDimension,
                'scope' => $extraEntry['dimensionScope'],
            ]);
        }
    }

    private function importCustomVariableSlots()
    {
        /** @var ImportConfiguration $importConfiguration */
        $importConfiguration = StaticContainer::get(ImportConfiguration::class);
        $numCustomVarSlots = (int) $importConfiguration->getNumCustomVariables();
        if ($numCustomVarSlots <= 0) {
            $this->logger->info("Using existing custom variable slots.");
            return;
        }

        if ($this->isPluginUnavailable('CustomVariables')) {
            $this->logger->warning("The CustomVariables plugin is not activated or present, skipping custom variable slot setting.");
            return;
        }

        $this->logger->info("Setting maximum number of custom variable slots to $numCustomVarSlots...");

        $command = "php " . PIWIK_INCLUDE_PATH . '/console';
        $domain = Config::getInstance()->getConfigHostnameIfSet();
        if (!empty($domain)) {
            $command .= ' --matomo-domain=' . $domain;
        }
        $command .= ' customvariables:set-max-custom-variables ' . $numCustomVarSlots;
        passthru($command);
    }

    public function import($idSite, $viewId, Date $start, Date $end, Lock $lock, $segment = '')
    {
        $date = null;

        try {
            $this->currentLock = $lock;
            $this->noDataMessageRemoved = false;
            $this->queryCount = 0;

            $endPlusOne = $end->addDay(1);

            if ($start->getTimestamp() >= $endPlusOne->getTimestamp()) {
                throw new \InvalidArgumentException("Invalid date range, start date is later than end date: {$start},{$end}");
            }

            $recordImporters = $this->getRecordImporters($idSite, $viewId);

            $site = new Site($idSite);
            for ($date = $start; $date->getTimestamp() < $endPlusOne->getTimestamp(); $date = $date->addDay(1)) {
                $this->logger->info("Importing data for GA View {viewId} for date {date}...", [
                    'viewId' => $viewId,
                    'date' => $date->toString(),
                ]);

                try {
                    $this->importDay($site, $date, $recordImporters, $segment);
                } finally {
                    // force delete all tables in case they aren't all freed
                    \Piwik\DataTable\Manager::getInstance()->deleteAll();
                }

                $this->importStatus->dayImportFinished($idSite, $date, $this->isMainImport);
            }

            $this->importStatus->finishImportIfNothingLeft($idSite);

            unset($recordImporters);
        } catch (DailyRateLimitReached $ex) {
            $this->importStatus->rateLimitReached($idSite);
            $this->logger->info($ex->getMessage());
            return true;
        } catch (MaxEndDateReached $ex) {
            $this->logger->info('Max end date reached. This occurs in Matomo for Wordpress installs when the importer tries to import days on or after the day Matomo for Wordpress installed.');

            if (!empty($date)) {
                $this->importStatus->dayImportFinished($idSite, $date, $this->isMainImport);
            }

            $this->importStatus->finishedImport($idSite);

            return true;
        } catch (\Exception $ex) {
            $this->onError($idSite, $ex, $date);
            return true;
        }

        return false;
    }

    /**
     * For use in RecordImporters that need to archive data for segments.
     * @var RecordImporter[] $recordImporters
     */
    public function importDay(Site $site, Date $date, $recordImporters, $segment, $plugin = null)
    {
        $maxEndDate = $this->endDate->getMaxEndDate();
        if ($maxEndDate && $maxEndDate->isEarlier($date)) {
            throw new MaxEndDateReached();
        }

        $archiveWriter = $this->makeArchiveWriter($site, $date, $segment, $plugin);
        $archiveWriter->initNewArchive();

        $recordInserter = new RecordInserter($archiveWriter);

        foreach ($recordImporters as $plugin => $recordImporter) {
            if (!$recordImporter->supportsSite()) {
                continue;
            }

            $this->logger->debug("Importing data for the {plugin} plugin.", [
                'plugin' => $plugin,
            ]);

            $recordImporter->setRecordInserter($recordInserter);

            $recordImporter->importRecords($date);

            // since we recorded some data, at some time, remove the no data message
            if (!$this->noDataMessageRemoved) {
                $this->removeNoDataMessage($site->getId());
                $this->noDataMessageRemoved = true;
            }

            if ($plugin === 'VisitsSummary') {
                /** @var \Piwik\Plugins\GoogleAnalyticsImporter\Importers\VisitsSummary\RecordImporter $visitsSummaryRecordImporter */
                $visitsSummaryRecordImporter = $recordImporter;

                $sessions = $visitsSummaryRecordImporter->getSessions();
                if ($sessions <= 0) {
                    $this->logger->info("Found 0 sessions for {$date} [segment = $segment], skipping rest of plugins for this day/segment.");
                    break;
                }
            }

            $this->currentLock->reexpireLock();
        }

        $archiveWriter->insertRecord(self::IS_IMPORTED_FROM_GA_NUMERIC, 1);
        $archiveWriter->finalizeArchive();

        $this->invalidator->markArchivesAsInvalidated([$site->getId()], [$date], 'week', new Segment($segment, [$site->getId()]));

        Common::destroy($archiveWriter);
    }

    private function makeArchiveWriter(Site $site, Date $date, $segment = '', $plugin = null)
    {
        $period = Factory::build('day', $date);
        $segment = new Segment($segment, [$site->getId()]);

        $params = new Parameters($site, $period, $segment);
        if (!empty($plugin)) {
            $params->setRequestedPlugin($plugin);
        }
        return new ArchiveWriter($params, $isTemp = false);
    }

    /**
     * @param $idSite
     * @param $viewId
     * @return RecordImporter[]
     * @throws \DI\NotFoundException
     */
    private function getRecordImporters($idSite, $viewId)
    {
        if (empty($this->recordImporters)) {
            $recordImporters = StaticContainer::get('GoogleAnalyticsImporter.recordImporters');

            $this->recordImporters = [];
            foreach ($recordImporters as $index => $recordImporterClass) {
                if (!defined($recordImporterClass . '::PLUGIN_NAME')) {
                    throw new \Exception("The $recordImporterClass record importer is missing the PLUGIN_NAME constant.");
                }

                $pluginName = $recordImporterClass::PLUGIN_NAME;
                if ($this->isPluginUnavailable($pluginName)) {
                    continue;
                }

                $this->recordImporters[$pluginName] = $recordImporterClass;
            }
        }

        $quotaUser = SettingsPiwik::getPiwikUrl();

        $gaQuery = new GoogleAnalyticsQueryService(
            $this->gaServiceReporting, $viewId, $this->getGoalMapping($idSite), $idSite, $quotaUser, StaticContainer::get(GoogleQueryObjectFactory::class), $this->logger);
        $gaQuery->setOnQueryMade(function () {
            ++$this->queryCount;
        });

        $instances = [];
        foreach ($this->recordImporters as $pluginName => $className) {
            $instances[$pluginName] = new $className($gaQuery, $idSite, $this->logger);
        }
        return $instances;
    }

    private function getGoalMapping($idSite)
    {
        $mapping = [];

        $goals = GoalsAPI::getInstance()->getGoals($idSite); // do not use request hooks, only interested in what's in the DB
        foreach ($goals as $idGoal => $goal) {
            $gaGoalId = $this->idMapper->getGoogleAnalyticsId('goal', $goal['idgoal'], $idSite);
            if ($gaGoalId === null) {
                $this->logNoGoalIdFoundException($goal);
                continue;
            }

            $mapping[$idGoal] = $gaGoalId;
        }

        return $mapping;
    }

    private function logNoGoalIdFoundException($goal)
    {
        $this->logger->warning("No GA goal ID found mapped for '{$goal['name']}' [idgoal = {$goal['idgoal']}]");
    }

    public function getQueryCount()
    {
        return $this->queryCount;
    }

    private function removeNoDataMessage($idSite)
    {
        $hadTrafficKey = 'SitesManagerHadTrafficInPast_' . (int) $idSite;
        Option::set($hadTrafficKey, 1);
    }

    private function goalExists(array $existingGoals, Google_Service_Analytics_Goal $gaGoal)
    {
        foreach ($existingGoals as $goal) {
            $gaGoalId = $this->idMapper->getGoogleAnalyticsId('goal', $goal['idgoal'], $goal['idsite']);
            if ($gaGoalId === null) {
                continue;
            }

            if ($gaGoalId == $gaGoal->getId()) {
                return true;
            }
        }
        return false;
    }

    private function customDimensionExists(array $existingCustomDimensions, \Google_Service_Analytics_CustomDimension $gaCustomDimension)
    {
        foreach ($existingCustomDimensions as $customDimension) {
            $customDimensionId = $this->idMapper->getGoogleAnalyticsId('customdimension', $customDimension['idcustomdimension'], $customDimension['idsite']);
            if ($customDimensionId === null) {
                continue;
            }

            if ('ga:dimension' . $customDimensionId == $gaCustomDimension->getId()) {
                return true;
            }
        }
        return false;
    }

    private function extraCustomDimensionExists(array $existingCustomDimensions, $gaDimensionName)
    {
        foreach ($existingCustomDimensions as $customDimension) {
            if ($customDimension['name'] == $gaDimensionName) {
                return true;
            }
        }
        return false;
    }

    private function isPluginUnavailable($pluginName)
    {
        return !Manager::getInstance()->isPluginActivated($pluginName)
            || !Manager::getInstance()->isPluginLoaded($pluginName)
            || !Manager::getInstance()->isPluginInFilesystem($pluginName);
    }

    private function checkExtraCustomDimensions($extraCustomDimensions)
    {
        if (empty($extraCustomDimensions)) {
            return [];
        }

        if (!is_array($extraCustomDimensions)) {
            throw new \Exception("Invalid value supplied for 'extraCustomDimensions': expected array, got " . gettype($extraCustomDimensions));
        }

        $cleaned = [];
        foreach ($extraCustomDimensions as $index => $field) {
            if (empty($field['gaDimension'])
                && empty($field['dimensionScope'])
            ) {
                continue;
            }

            if (empty($field['gaDimension'])) {
                throw new \Exception("Invalid value supplied for 'extraCustomDimensions': field #$index is missing the gaDimension property.");
            }

            if (empty($field['dimensionScope'])) {
                throw new \Exception("Invalid value supplied for 'extraCustomDimensions': field #$index is missing the dimensionScope property.");
            }

            $dimensionScope = $field['dimensionScope'];
            if ($dimensionScope !== 'action'
                && $dimensionScope !== 'visit'
            ) {
                throw new \Exception("Invalid value supplied for 'extraCustomDimensions': field #$index has unknown dimensionScope '$dimensionScope'.");
            }

            $cleaned[] = [
                'gaDimension' => $field['gaDimension'],
                'dimensionScope' => $field['dimensionScope'],
            ];
        }
        return $cleaned;
    }

    private function isGaAuthroizationError(\Exception $ex)
    {
        if ($ex->getCode() != 403) {
            return false;
        }

        $messageContent = @json_decode($ex->getMessage(), true);
        if (isset($messageContent['error']['message'])
            && stristr($messageContent['error']['message'], 'Request had insufficient authentication scopes')
        ) {
            return true;
        }

        return false;
    }

    private function onError($idSite, \Exception $ex, Date $date = null)
    {
        if ($this->isGaAuthroizationError($ex)) {
            $this->importStatus->erroredImport($idSite, Piwik::translate('GoogleAnalyticsImporter_InsufficientScopes'));
        } else {
            $dateStr = isset($date) ? $date->toString() : '(unknown)';
            $this->importStatus->erroredImport($idSite, "Error on day $dateStr, " . $ex->getMessage());
        }
    }
}
