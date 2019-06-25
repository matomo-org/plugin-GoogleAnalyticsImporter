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

    public function queryGoogleAnalyticsApi(Date $day)
    {
        $idMapper = StaticContainer::get(IdMapper::class);

        $customDimensions = API::getInstance()->getConfiguredCustomDimensions($this->getIdSite());
        foreach ($customDimensions as $dimension) {
            $gaId = $idMapper->getGoogleAnalyticsId('customdimension', $dimension['idcustomdimension']);

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

        // TODO: should auto sort based on metrics requested
        $table = $gaQuery->query($day, $dimensions = [$dimension], $this->getVisitMetrics());
        foreach ($table->getRows() as $row) {
            $label = $row->getColumn($dimension);
            if (empty($label)) {
                $label = '(not set)'; // TODO: need to be able to translate values like this somehow
            }
            $this->addRowToTable($record, $row, $label);
        }

        Common::destroy($table);

        return $record;
    }

    private function insertCustomDimensionRecord(DataTable $record, $dimension)
    {
        $blob = $record->getSerialized(
            $this->maximumRowsInDataTableLevelZero, $this->maximumRowsInSubDataTable,
            $columnToSort = Metrics::INDEX_NB_VISITS
        );

        $recordName = Archiver::buildRecordNameForCustomDimensionId($dimension['idcustomdimension']);

        $this->insertBlobRecord($recordName, $blob);

        unset($blob);
    }
}