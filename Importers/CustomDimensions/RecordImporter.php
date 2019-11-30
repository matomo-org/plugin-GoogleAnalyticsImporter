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
use Psr\Log\LoggerInterface;

class RecordImporter extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporter
{
    const PLUGIN_NAME = 'CustomDimensions';

    private $maximumRowsInDataTableLevelZero;
    private $maximumRowsInSubDataTable;

    public function __construct(GoogleAnalyticsQueryService $gaQuery, $idSite, LoggerInterface $logger)
    {
        parent::__construct($gaQuery, $idSite, $logger);

        $this->maximumRowsInDataTableLevelZero = Config::getInstance()->General['datatable_archiving_maximum_rows_custom_variables'];
        $this->maximumRowsInSubDataTable = Config::getInstance()->General['datatable_archiving_maximum_rows_subtable_custom_variables'];
    }

    public function importRecords(Date $day)
    {
        $idMapper = StaticContainer::get(IdMapper::class);

        $customDimensions = API::getInstance()->getConfiguredCustomDimensions($this->getIdSite());
        foreach ($customDimensions as $dimension) {
            $gaId = $idMapper->getGoogleAnalyticsId('customdimension', $dimension['idcustomdimension']);
            if ($gaId === null) {
                throw new \Exception("Cannot find Google Analytics entity ID for custom dimension (ID = {$dimension['idcustomdimension']})");
            }

            $record = $this->queryCustomDimension($gaId, $day, $dimension);
            $this->insertCustomDimensionRecord($record, $dimension);
            Common::destroy($table);
        }
    }

    private function queryCustomDimension($gaId, Date $day, $dimensionObj)
    {
        $gaQuery = $this->getGaQuery();
        $dimension = 'ga:dimension' . $gaId;

        $record = new DataTable();

        if ($dimensionObj['scope'] === CustomDimensions::SCOPE_VISIT) {
            $metricsToQuery = $this->getConversionAwareVisitMetrics();
        } else if ($dimensionObj['scope'] === CustomDimensions::SCOPE_ACTION) {
            $metricsToQuery = $this->getPageMetrics();
        } else {
            return $record;
        }

        $table = $gaQuery->query($day, $dimensions = [$dimension], $metricsToQuery);
        foreach ($table->getRows() as $row) {
            $label = $row->getMetadata($dimension);
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
            $table = $gaQuery->query($day, $dimensions = [$dimension], [Metrics::INDEX_NB_VISITS, Metrics::INDEX_BOUNCE_COUNT], [
                'orderBys' => [
                    ['field' => 'ga:sessions', 'order' => 'descending'],
                    ['field' => $dimension, 'order' => 'ascending'],
                ],
                'mappings' => [
                    Metrics::INDEX_NB_VISITS => 'ga:uniquePageviews',
                ],
            ]);

            foreach ($table->getRows() as $row) { // TODO: lots of code redundancy here, can create a helper
                $label = $row->getMetadata($dimension);
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
            $exitPageMetrics = [
                Metrics::INDEX_PAGE_EXIT_NB_VISITS,
                Metrics::INDEX_PAGE_ENTRY_NB_VISITS,
                Metrics::INDEX_PAGE_ENTRY_NB_ACTIONS,
                Metrics::INDEX_PAGE_ENTRY_SUM_VISIT_LENGTH,
            ];

            $table = $gaQuery->query($day, $dimensions = [$dimension], $exitPageMetrics, [
                'orderBys' => [
                    ['field' => 'ga:exits', 'order' => 'descending'],
                    ['field' => $dimension, 'order' => 'ascending'],
                ],
            ]);

            foreach ($table->getRows() as $row) {
                $label = $row->getMetadata($dimension);
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
        $this->insertRecord($recordName, $record, $this->maximumRowsInDataTableLevelZero, $this->maximumRowsInSubDataTable,
            $columnToSort = Metrics::INDEX_NB_VISITS);
    }
}