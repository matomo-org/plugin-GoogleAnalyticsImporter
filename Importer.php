<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter;


use Piwik\ArchiveProcessor\Parameters;
use Piwik\Container\StaticContainer;
use Piwik\DataAccess\ArchiveWriter;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Period\Factory;
use Piwik\Plugin\Manager;
use Piwik\Plugin\Report;
use Piwik\Plugin\ReportsProvider;
use Piwik\Plugins\SitesManager\API;
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
     * @var array|null
     */
    private $recordImporters;

    public function __construct(ReportsProvider $reportsProvider, \Google_Client $client, LoggerInterface $logger)
    {
        $this->reportsProvider = $reportsProvider;
        $this->gaService = new \Google_Service_Analytics($client);
        $this->logger = $logger;
    }

    public function makeSite($accountId, $propertyId, $viewId)
    {
        $webproperty = $this->gaService->management_webproperties->get($accountId, $propertyId);
        $view = $this->gaService->management_profiles->get($accountId, $propertyId, $viewId);

        // TODO: mapping site settings?
        // TODO: detecting excluded ips/user agents might be impossible
        return API::getInstance()->addSite(
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

            $this->recordImporters = StaticContainer::get('GoogleAnalyticsImporter.recordImporters');
            foreach ($this->recordImporters as $index => $recordImporterClass) {
                $pluginName = $recordImporterClass::PLUGIN_NAME;
                if (!in_array($pluginName, $activatedPlugins)) {
                    unset($this->recordImporters[$index]);
                }
            }
            $this->recordImporters = array_values($this->recordImporters);
        }

        $gaQuery = new GoogleAnalyticsQueryService($this->gaService, $viewId);

        $instances = [];
        foreach ($this->recordImporters as $className) {
            $instances[] = new $className($gaQuery, $idSite);
        }
        return $instances;
    }
}
