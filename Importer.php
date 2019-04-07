<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter;


use Piwik\ArchiveProcessor\Parameters;
use Piwik\DataAccess\ArchiveWriter;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Period\Factory;
use Piwik\Plugin\Report;
use Piwik\Plugin\ReportsProvider;
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
     * @var \Google_Client
     */
    private $client;

    /**
     * @var GoogleAnalyticsQueryFactory
     */
    private $gaQueryFactory;

    /**
     * @var GoogleAnalyticsResponseConverter
     */
    private $gaResponseConverter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \Google_Service_Analytics
     */
    private $gaService;

    public function __construct(ReportsProvider $reportsProvider, \Google_Client $client, GoogleAnalyticsQueryFactory $gaQueryFactory,
                                GoogleAnalyticsResponseConverter $gaResponseConverter, LoggerInterface $logger)
    {
        $this->reportsProvider = $reportsProvider;
        $this->client = $client;
        $this->logger = $logger;
        $this->gaQueryFactory = $gaQueryFactory;
        $this->gaResponseConverter = $gaResponseConverter;

        $this->gaService = new \Google_Service_Analytics($this->client);
    }

    public function makeSite($accountId, $propertyId)
    {
        // TODO
    }

    public function import($idSite, $viewId, Date $start, Date $end)
    {
        if ($start->getTimestamp() >= $end->getTimestamp()) {
            throw new \InvalidArgumentException("Invalid date range, start date is later than end date: {$start},{$end}");
        }

        $site = new Site($idSite);
        $reports = $this->reportsProvider->getAllReports();

        for ($date = $start; $date->getTimestamp() < $end->getTimestamp(); $date = $date->addDay(1)) {
            $archiveWriter = $this->makeArchiveWriter($site, $date);
            $archiveWriter->initNewArchive();

            $this->logger->info("Importing data for GA View {viewId} for date {date}...", [
                'viewId' => $viewId,
                'date' => $date->toString(),
            ]);

            foreach ($reports as $report) {
                $dataTable = $this->importReport($viewId, $date, $report);
                $this->insertArchive($dataTable); // TODO: this has to insert them into the proper record...
            }

            $archiveWriter->finalizeArchive();
        }
    }

    public function importReport($viewId, Date $date, Report $report)
    {
        $this->logger->debug("Importing report {$report->getName()} from GA View $viewId for day {$date->toString()}.");

        $gaQuery = $this->gaQueryFactory->makeGaQuery($viewId, $date, $report); // TODO

        /** @var \Google_Service_Analytics_GaData $response */
        $response = $this->gaService->data_ga->get('ga:' . $viewId, $date->toString(), $date->toString(), $gaQuery['metrics'],
            $gaQuery['optParams']);

        return $this->gaResponseConverter->makeDataTable($response); // TODO
    }

    private function insertArchive(DataTable $dataTable)
    {
        // TODO
    }

    private function makeArchiveWriter(Site $site, Date $date)
    {
        $period = Factory::build('day', $date);
        $segment = new Segment('', [$site->getId()]);

        $params = new Parameters($site, $period, $segment);
        return new ArchiveWriter($params, $isTemp = false);
    }
}
/*
 *  $actions = [];
        foreach ($dimensionMapper->getVisitorDimensionMappings() as $matomoDimension => $googleDimension) {
            $dimensions = array_merge($actionIdentifierDimensions, [$googleDimension]);
            $dimensions = array_unique($dimensions);

            $data = $service->data_ga->get('ga:' . $viewId, '2019-01-01', '2019-01-01', 'ga:users,ga:hits', [
                'dimensions' => implode(',', $dimensions),
            ]);

            $rows = $data->getRows();
            $this->mergeReport($actions, $rows, $actionIdentifierDimensions, $dimensions);

            print $googleDimension . "\n";
            print_r($data->getRows());
        }

        print "result:\n";
        print_r($actions);
 */