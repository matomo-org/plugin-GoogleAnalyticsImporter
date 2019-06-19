<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter;


use Google_Service_Analytics_Goal;
use Piwik\ArchiveProcessor\Parameters;
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\DataAccess\ArchiveWriter;
use Piwik\Date;
use Piwik\Period\Factory;
use Piwik\Plugin\Manager;
use Piwik\Plugin\ReportsProvider;
use Piwik\Plugins\SitesManager\API as SitesManagerAPI;
use Piwik\Plugins\Goals\API as GoalsAPI;
use Piwik\Plugins\CustomDimensions\API as CustomDimensionsAPI;
use Piwik\Segment;
use Piwik\Site;
use Psr\Log\LoggerInterface;

class Importer
{
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

    public function __construct(ReportsProvider $reportsProvider, \Google_Client $client, LoggerInterface $logger, GoogleGoalMapper $goalMapper,
                                GoogleCustomDimensionMapper $customDimensionMapper, IdMapper $idMapper)
    {
        $this->reportsProvider = $reportsProvider;
        $this->gaService = new \Google_Service_Analytics($client);
        $this->gaServiceReporting = new \Google_Service_AnalyticsReporting($client);
        $this->logger = $logger;
        $this->goalMapper = $goalMapper;
        $this->customDimensionMapper = $customDimensionMapper;
        $this->idMapper = $idMapper;
    }

    public function makeSite($accountId, $propertyId, $viewId)
    {
        $webproperty = $this->gaService->management_webproperties->get($accountId, $propertyId);
        $view = $this->gaService->management_profiles->get($accountId, $propertyId, $viewId);

        // TODO: mapping site settings?
        // TODO: detecting excluded ips/user agents might be impossible
        $idSite = SitesManagerAPI::getInstance()->addSite(
            $siteName = $webproperty->getName(),
            $urls = [$webproperty->getWebsiteUrl()],
            $ecommerce = $view->eCommerceTracking ? 1 : 0,
            $siteSearch = !empty($view->siteSearchQueryParameters),
            $searchKeywordParams = $view->siteSearchQueryParameters, // TODO: is this right?
            $searchCategoryParams = $view->siteSearchCategoryParameters,
            $excludedIps = null,
            $excludedParams = $view->excludeQueryParameters, // TODO: correct?
            $timezone = $view->timezone,
            $currency = $view->currency, // TODO: need to map?
            $group = null,
            $startDate = Date::factory($webproperty->getCreated())->toString()
        );

        $this->importGoals($idSite, $accountId, $propertyId, $viewId);
        $this->importCustomDimensions($idSite, $accountId, $propertyId);
        $this->importCustomDimensionSlots();

        return $idSite;
    }

    private function importGoals($idSite, $accountId, $propertyId, $viewId)
    {
        $goals = $this->gaService->management_goals->listManagementGoals($accountId, $propertyId, $viewId);

        /** @var Google_Service_Analytics_Goal $gaGoal */
        foreach ($goals->getItems() as $gaGoal) {
            try {
                $goal = $this->goalMapper->map($gaGoal);
            } catch (CannotImportGoalException $ex) {
                $this->logger->warning($ex->getMessage());
                $this->logger->warning('Importing this goal as a manually triggered goal. Metrics for this goal will be available, but tracking will not work for this goal in Matomo.');

                $goal = $this->goalMapper->mapManualGoal($gaGoal);
            }

            // TODO: should probably use Request::processRequest here for hooks
            $idGoal = GoalsAPI::getInstance()->addGoal($idSite, $gaGoal->getName(), $goal['match_attribute'], $goal['pattern'], $goal['pattern_type'],
                $goal['case_sensitive'], $goal['revenue'], $goal['allow_multiple_conversions'], $goal['description'], $goal['use_event_value_as_revenue']);

            if (!empty($goal['funnel'])) {
                StaticContainer::get(\Piwik\Plugins\Funnels\Model\FunnelsModel::class)->clearGoalsCache();
                \Piwik\Plugins\Funnels\API::getInstance()->setGoalFunnel($idSite, $idGoal, true, $goal['funnel']);
            }
        }
    }

    private function importCustomDimensions($idSite, $accountId, $propertyId)
    {
        $customDimensions = $this->gaService->management_customDimensions->listManagementCustomDimensions($accountId, $propertyId);

        /** @var \Google_Service_Analytics_CustomDimension $gaCustomDimension */
        foreach ($customDimensions->getItems() as $gaCustomDimension) {
            preg_match('/ga:dimension([0-9]+)/', $gaCustomDimension->getId(), $matches);
            $gaId = $matches[1]; // TODO: check the match was successful and log if not

            try {
                $customDimension = $this->customDimensionMapper->map($gaCustomDimension);
            } catch (CannotImportCustomDimensionException $ex) {
                $this->logger->warning($ex->getMessage());
                $this->logger->warning("Skipping this custom dimension.");
            }

            $idDimension = CustomDimensionsAPI::getInstance()->configureNewCustomDimension(
                $idSite, $customDimension['name'], $customDimension['scope'], $customDimension['active'], $customDimension['extractions'],
                $customDimension['case_sensitive']);

            $this->idMapper->mapEntityId('customdimension', $gaId, $idDimension);
        }
    }

    private function importCustomDimensionSlots()
    {
        /** @var ImportConfiguration $importConfiguration */
        $importConfiguration = StaticContainer::get(ImportConfiguration::class);
        $numCustomVarSlots = (int) $importConfiguration->getNumCustomVariables();

        $this->logger->info("Setting maximum number of custom variable slots to $numCustomVarSlots...");

        $command = "php " . PIWIK_INCLUDE_PATH . '/console ';
        $domain = Config::getInstance()->getConfigHostnameIfSet();
        if (!empty($domain)) {
            $command .= '--matomo-domain=' . $domain;
        }
        $command .= 'customvariables:set-max-custom-variables ' . $numCustomVarSlots;
        passthru($command);
    }

    public function import($idSite, $viewId, Date $start, Date $end)
    {
        if ($start->getTimestamp() >= $end->getTimestamp()) {
            throw new \InvalidArgumentException("Invalid date range, start date is later than end date: {$start},{$end}");
        }

        $recordImporters = $this->getRecordImporters($idSite, $viewId);

        $site = new Site($idSite);
        for ($date = $start; $date->getTimestamp() < $end->getTimestamp(); $date = $date->addDay(1)) {
            $archiveWriter = $this->makeArchiveWriter($site, $date);
            $archiveWriter->initNewArchive();

            $this->logger->info("Importing data for GA View {viewId} for date {date}...", [
                'viewId' => $viewId,
                'date' => $date->toString(),
            ]);

            foreach ($recordImporters as $plugin => $recordImporter) {
                $this->logger->debug("Importing data for the {plugin} plugin.", [
                    'plugin' => $plugin,
                ]);

                $recordImporter->setArchiveWriter($archiveWriter);
                $recordImporter->queryGoogleAnalyticsApi($date);
            }

            $archiveWriter->finalizeArchive();
        }
    }

    private function makeArchiveWriter(Site $site, Date $date)
    {
        $period = Factory::build('day', $date);
        $segment = new Segment('', [$site->getId()]);

        $params = new Parameters($site, $period, $segment);
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
            $activatedPlugins = Manager::getInstance()->getActivatedPlugins();

            $recordImporters = StaticContainer::get('GoogleAnalyticsImporter.recordImporters');

            $this->recordImporters = [];
            foreach ($recordImporters as $index => $recordImporterClass) {
                if (!defined($recordImporterClass . '::PLUGIN_NAME')) {
                    throw new \Exception("The $recordImporterClass record importer is missing the PLUGIN_NAME constant.");
                }

                $pluginName = $recordImporterClass::PLUGIN_NAME;
                if (!in_array($pluginName, $activatedPlugins)) {
                    continue;
                }

                $this->recordImporters[$pluginName] = $recordImporterClass;
            }
        }

        $gaQuery = new GoogleAnalyticsQueryService($this->gaServiceReporting, $viewId, $this->getGoalMapping($idSite), $idSite);

        $instances = [];
        foreach ($this->recordImporters as $pluginName => $className) {
            $instances[$pluginName] = new $className($gaQuery, $idSite, $this->logger);
        }
        return $instances;
    }

    private function getGoalMapping($idSite)
    {
        $mapping = [];

        $goals = GoalsAPI::getInstance()->getGoals($idSite); // should not use request hooks, only interested in what's in the DB
        foreach ($goals as $idGoal => $goal) {
            // TODO: should we store the GA goal ID somewhere else? not exactly sure where we'd put it, but we need it to be available in case of re-trying an import
            $gaGoalId = $this->goalMapper->getGoalIdFromDescription($goal);
            $mapping[$idGoal] = $gaGoalId;
        }

        return $mapping;
    }
}
