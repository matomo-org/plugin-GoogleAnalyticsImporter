<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\Importers\CustomDimensions;

use Piwik\Common;
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Metrics;
use Piwik\Plugins\CustomDimensions\API;
use Piwik\Plugins\CustomDimensions\Archiver;
use Piwik\Plugins\CustomDimensions\CustomDimensions;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleAnalyticsQueryService;
use Piwik\Plugins\GoogleAnalyticsImporter\IdMapper;
use Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus;
use Piwik\Plugins\MobileAppMeasurable\Type;
use Piwik\Site;
use Piwik\Log\LoggerInterface;
class RecordImporter extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporter
{
    const PLUGIN_NAME = 'CustomDimensions';
    private $maximumRowsInDataTableLevelZero;
    private $maximumRowsInSubDataTable;
    private $isMobileApp;
    private $uniquePageviewsMetric;
    private $hitsMetric;
    private $entryPageDimension;
    private $exitPageDimension;
    public function __construct(GoogleAnalyticsQueryService $gaQuery, $idSite, LoggerInterface $logger)
    {
        parent::__construct($gaQuery, $idSite, $logger);
        $this->maximumRowsInDataTableLevelZero = Config::getInstance()->General['datatable_archiving_maximum_rows_custom_dimensions'];
        $this->maximumRowsInSubDataTable = Config::getInstance()->General['datatable_archiving_maximum_rows_subtable_custom_dimensions'];
        $this->isMobileApp = Site::getTypeFor($this->getIdSite()) == Type::ID;
        $this->uniquePageviewsMetric = $this->isMobileApp ? 'ga:uniqueScreenviews' : 'ga:uniquePageviews';
        $this->hitsMetric = $this->isMobileApp ? 'ga:screenviews' : 'ga:pageviews';
        $this->entryPageDimension = $this->isMobileApp ? 'ga:landingScreenName' : 'ga:landingPagePath';
        $this->exitPageDimension = $this->isMobileApp ? 'ga:exitScreenName' : 'ga:exitPagePath';
    }
    public function importRecords(Date $day)
    {
        $idMapper = StaticContainer::get(IdMapper::class);
        $importStatusService = StaticContainer::get(ImportStatus::class);
        $importStatus = $importStatusService->getImportStatus($this->getIdSite());
        $extraCustomDimensions = !empty($importStatus['extra_custom_dimensions']) ? $importStatus['extra_custom_dimensions'] : [];
        $extraCustomDimensions = array_column($extraCustomDimensions, 'dimensionScope', 'gaDimension');
        $customDimensions = API::getInstance()->getConfiguredCustomDimensions($this->getIdSite());
        foreach ($customDimensions as $dimension) {
            $idCustomDimension = $dimension['idcustomdimension'];
            $customDimensionName = $dimension['name'];
            $gaId = $idMapper->getGoogleAnalyticsId('customdimension', $idCustomDimension, $dimension['idsite']);
            if ($gaId !== null) {
                $record = $this->queryCustomDimension($day, $dimension, 'ga:dimension' . $gaId);
                $this->insertCustomDimensionRecord($record, $dimension);
                Common::destroy($record);
            } else {
                if (isset($extraCustomDimensions[$customDimensionName])) {
                    $record = $this->queryCustomDimension($day, $dimension, $customDimensionName);
                    $this->insertCustomDimensionRecord($record, $dimension);
                    Common::destroy($record);
                }
            }
        }
    }
    private function queryCustomDimension(Date $day, $dimensionObj, $gaDimension)
    {
        $gaQuery = $this->getGaQuery();
        $record = new DataTable();
        $options = [];
        if ($dimensionObj['scope'] === CustomDimensions::SCOPE_VISIT) {
            $metricsToQuery = $this->getConversionAwareVisitMetrics();
        } else {
            if ($dimensionObj['scope'] === CustomDimensions::SCOPE_ACTION) {
                $metricsToQuery = $this->getPageMetrics();
                $options['mappings'] = [Metrics::INDEX_PAGE_NB_HITS => $this->hitsMetric];
            } else {
                return $record;
            }
        }
        $table = $gaQuery->query($day, $dimensions = [$gaDimension], $metricsToQuery, $options);
        foreach ($table->getRows() as $row) {
            $label = $row->getMetadata($gaDimension);
            if (empty($label)) {
                $label = parent::NOT_SET_IN_GA_LABEL;
            }
            $row->deleteMetadata();
            $this->addRowToTable($record, $row, $label);
        }
        Common::destroy($table);
        // if scope is action, we also need to query exit page metrics and visit metrics (done separately
        // see Importers::getPageMetrics for more info)
        if ($dimensionObj['scope'] === CustomDimensions::SCOPE_ACTION) {
            $table = $gaQuery->query($day, $dimensions = [$gaDimension], [Metrics::INDEX_NB_VISITS, Metrics::INDEX_BOUNCE_COUNT], ['orderBys' => [['field' => $this->uniquePageviewsMetric, 'order' => 'descending'], ['field' => $gaDimension, 'order' => 'ascending']], 'mappings' => [Metrics::INDEX_NB_VISITS => $this->uniquePageviewsMetric]]);
            foreach ($table->getRows() as $row) {
                // TODO: lots of code redundancy here, can create a helper
                $label = $row->getMetadata($gaDimension);
                if (empty($label)) {
                    $label = parent::NOT_SET_IN_GA_LABEL;
                }
                $row->deleteMetadata();
                $tableRow = $record->getRowFromLabel($label);
                if (!empty($tableRow)) {
                    $tableRow->sumRow($row);
                }
            }
            Common::destroy($table);
            // not querying for unique visitors since we can't sum those in case of exit page path being different,
            // but dimension value being the same
            $exitPageMetrics = [Metrics::INDEX_PAGE_EXIT_NB_VISITS];
            $table = $gaQuery->query($day, $dimensions = [$this->exitPageDimension, $gaDimension], $exitPageMetrics, ['orderBys' => [['field' => 'ga:exits', 'order' => 'descending'], ['field' => $gaDimension, 'order' => 'ascending']]]);
            foreach ($table->getRows() as $row) {
                $label = $row->getMetadata($gaDimension);
                if (empty($label)) {
                    $label = parent::NOT_SET_IN_GA_LABEL;
                }
                $row->deleteMetadata();
                $tableRow = $record->getRowFromLabel($label);
                if (!empty($tableRow)) {
                    $tableRow->sumRow($row);
                }
            }
            Common::destroy($table);
            $entryPageMetrics = [Metrics::INDEX_PAGE_ENTRY_NB_VISITS, Metrics::INDEX_PAGE_ENTRY_NB_ACTIONS, Metrics::INDEX_PAGE_ENTRY_SUM_VISIT_LENGTH];
            $table = $gaQuery->query($day, $dimensions = [$this->entryPageDimension, $gaDimension], $entryPageMetrics, ['orderBys' => [['field' => 'ga:entrances', 'order' => 'descending'], ['field' => $gaDimension, 'order' => 'ascending']]]);
            foreach ($table->getRows() as $row) {
                $label = $row->getMetadata($gaDimension);
                if (empty($label)) {
                    $label = parent::NOT_SET_IN_GA_LABEL;
                }
                $row->deleteMetadata();
                $tableRow = $record->getRowFromLabel($label);
                if (!empty($tableRow)) {
                    $tableRow->sumRow($row);
                }
            }
            Common::destroy($table);
        }
        return $record;
    }
    private function insertCustomDimensionRecord(DataTable $record, $dimension)
    {
        $recordName = Archiver::buildRecordNameForCustomDimensionId($dimension['idcustomdimension']);
        $this->insertRecord($recordName, $record, $this->maximumRowsInDataTableLevelZero, $this->maximumRowsInSubDataTable, $columnToSort = Metrics::INDEX_NB_VISITS);
    }
}
