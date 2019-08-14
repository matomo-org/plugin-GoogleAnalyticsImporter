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
use Piwik\ArchiveProcessor\Parameters;
use Piwik\Concurrency\Lock;
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\DataAccess\ArchiveWriter;
use Piwik\Date;
use Piwik\Option;
use Piwik\Period\Factory;
use Piwik\Piwik;
use Piwik\Plugin\Manager;
use Piwik\Plugin\ReportsProvider;
use Piwik\Plugins\Goals\API;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\DailyRateLimitReached;
use Piwik\Plugins\SitesManager\API as SitesManagerAPI;
use Piwik\Plugins\Goals\API as GoalsAPI;
use Piwik\Plugins\CustomDimensions\API as CustomDimensionsAPI;
use Piwik\Segment;
use Piwik\Site;
use Piwik\Tracker\GoalManager;
use Psr\Log\LoggerInterface;

class Importer
{
    const LOCK_TTL = 300; // lock will expire 5 minutes after inactivity

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
    private $queryCount;

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

    public function __construct(ReportsProvider $reportsProvider, \Google_Service_Analytics $gaService, \Google_Service_AnalyticsReporting $gaReportingService,
                                LoggerInterface $logger, GoogleGoalMapper $goalMapper, GoogleCustomDimensionMapper $customDimensionMapper,
                                IdMapper $idMapper, ImportStatus $importStatus)
    {
        $this->reportsProvider = $reportsProvider;
        $this->gaService = $gaService;
        $this->gaServiceReporting = $gaReportingService;
        $this->logger = $logger;
        $this->goalMapper = $goalMapper;
        $this->customDimensionMapper = $customDimensionMapper;
        $this->idMapper = $idMapper;
        $this->importStatus = $importStatus;
    }

    public function makeSite($accountId, $propertyId, $viewId)
    {
        $webproperty = $this->gaService->management_webproperties->get($accountId, $propertyId);
        $view = $this->gaService->management_profiles->get($accountId, $propertyId, $viewId);

        $idSite = SitesManagerAPI::getInstance()->addSite(
            $siteName = $webproperty->getName(),
            $urls = [$webproperty->getWebsiteUrl()],
            $ecommerce = $view->eCommerceTracking ? 1 : 0,
            $siteSearch = !empty($view->siteSearchQueryParameters),
            $searchKeywordParams = $view->siteSearchQueryParameters,
            $searchCategoryParams = $view->siteSearchCategoryParameters,
            $excludedIps = null,
            $excludedParams = $view->excludeQueryParameters,
            $timezone = $view->timezone,
            $currency = $view->currency,
            $group = null,
            $startDate = Date::factory($webproperty->getCreated())->toString()
        );

        $this->importStatus->startingImport($propertyId, $accountId, $viewId, $idSite);

        return $idSite;
    }

    public function importEntities($idSite, $accountId, $propertyId, $viewId)
    {
        try {
            $this->importGoals($idSite, $accountId, $propertyId, $viewId);
            $this->importCustomDimensions($idSite, $accountId, $propertyId);
            $this->importCustomVariableSlots();
        } catch (\Exception $ex) {
            $this->importStatus->erroredImport($idSite, $ex->getMessage());

            throw $ex;
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

            $this->idMapper->mapEntityId('goal', $gaGoal->getId(), $idGoal);

            if (!empty($goal['funnel'])) {
                StaticContainer::get(\Piwik\Plugins\Funnels\Model\FunnelsModel::class)->clearGoalsCache();
                \Piwik\Plugins\Funnels\API::getInstance()->setGoalFunnel($idSite, $idGoal, true, $goal['funnel']);
            }
        }
    }

    private function importCustomDimensions($idSite, $accountId, $propertyId)
    {
        if ($this->isPluginUnavailable('CustomDimensions')) {
            $this->logger->warning("The CustomDimensions plugin is not activated or present, skipping goal import.");
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

            $idDimension = CustomDimensionsAPI::getInstance()->configureNewCustomDimension(
                $idSite, $customDimension['name'], $customDimension['scope'], $customDimension['active'], $customDimension['extractions'],
                $customDimension['case_sensitive']);

            $this->idMapper->mapEntityId('customdimension', $gaId, $idDimension);
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
        try {
            $this->currentLock = $lock;
            $this->noDataMessageRemoved = false;
            $this->queryCount = 0;

            $endPlusOne = $end->addDay(1);

            if ($start->getTimestamp() >= $endPlusOne->getTimestamp()) {
                throw new \InvalidArgumentException("Invalid date range, start date is later than end date: {$start},{$end}");
            }

            $status = $this->importStatus->getImportStatus($idSite);

            $recordImporters = $this->getRecordImporters($idSite, $viewId);

            $site = new Site($idSite);
            for ($date = $start; $date->getTimestamp() < $endPlusOne->getTimestamp(); $date = $date->addDay(1)) {
                $this->logger->info("Importing data for GA View {viewId} for date {date}...", [
                    'viewId' => $viewId,
                    'date' => $date->toString(),
                ]);

                $this->importDay($site, $date, $recordImporters, $segment);

                $this->importStatus->dayImportFinished($idSite, $date);
            }

            if (!empty($status['import_range_end'])
                && $end->toString() == $status['import_range_end']
            ) {
                $this->importStatus->finishedImport($idSite);
            }
        } catch (DailyRateLimitReached $ex) {
            $this->importStatus->rateLimitReached($idSite);
            throw $ex;
        } catch (\Exception $ex) {
            $this->importStatus->erroredImport($idSite, $ex->getMessage());

            throw $ex;
        }
    }

    /**
     * For use in RecordImporters that need to archive data for segments.
     * @var RecordImporter[] $recordImporters
     */
    public function importDay(Site $site, Date $date, $recordImporters, $segment, $plugin = null)
    {
        $archiveWriter = $this->makeArchiveWriter($site, $date, $segment, $plugin);
        $archiveWriter->initNewArchive();


        foreach ($recordImporters as $plugin => $recordImporter) {
            $this->logger->debug("Importing data for the {plugin} plugin.", [
                'plugin' => $plugin,
            ]);

            // TODO: change visitor frequency importer
            // TODO: if two record importers use the same segment, this will likely break (since one archive will have metrics for one plugin, another archive for another plugin, so queries won't get the full data)

            $recordImporter->setArchiveWriter($archiveWriter);
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
                    $this->logger->info("Found 0 sessions for {$date}, skipping rest of plugins for this day.");
                    break;
                }
            }

            $this->currentLock->expireLock(self::LOCK_TTL);
        }

        $archiveWriter->finalizeArchive();
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

        $gaQuery = new GoogleAnalyticsQueryService($this->gaServiceReporting, $viewId, $this->getGoalMapping($idSite), $idSite, $this->logger);
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
            $gaGoalId = $this->idMapper->getGoogleAnalyticsId('goal', $goal['idgoal']);
            if ($gaGoalId === null) {
                $this->throwNowGoalIdFoundException($goal);
            }

            $mapping[$idGoal] = $gaGoalId;
        }

        return $mapping;
    }

    private function throwNowGoalIdFoundException($goal)
    {
        throw new \Exception("No GA goal ID found in goal description for '{$goal['name']}' [idgoal = {$goal['idgoal']}]");
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
            $gaGoalId = $this->idMapper->getGoogleAnalyticsId('goal', $goal['idgoal']);
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
            $customDimensionId = $this->idMapper->getGoogleAnalyticsId('customdimension', $customDimension['idcustomdimension']);
            if ($customDimensionId === null) {
                continue;
            }

            if ('ga:dimension' . $customDimensionId == $gaCustomDimension->getId()) {
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
}
