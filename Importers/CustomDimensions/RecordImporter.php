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
use Piwik\Plugins\GoogleAnalyticsImporter\GoogleAnalyticsQueryService;
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

            $record = $this->queryCustomDimension($gaId, $day);
            $this->insertCustomDimensionRecord($record, $dimension);
            Common::destroy($table);
        }
    }

    private function queryCustomDimension($gaId, Date $day)
    {
        $gaQuery = $this->getGaQuery();
        $dimension = 'ga:dimension' . $gaId;

        $record = new DataTable();

        $table = $gaQuery->query($day, $dimensions = [$dimension], $this->getVisitMetrics());
        foreach ($table->getRows() as $row) {
            $label = $row->getMetadata($dimension);
            if (empty($label)) {
                $label = parent::NOT_SET_IN_GA_LABEL;
            }

            $row->deleteMetadata();
            $this->addRowToTable($record, $row, $label);
        }

        Common::destroy($table);

        return $record;
    }

    private function insertCustomDimensionRecord(DataTable $record, $dimension)
    {
        $recordName = Archiver::buildRecordNameForCustomDimensionId($dimension['idcustomdimension']);
        $this->insertRecord($recordName, $record, $this->maximumRowsInDataTableLevelZero, $this->maximumRowsInSubDataTable,
            $columnToSort = Metrics::INDEX_NB_VISITS);
    }
}