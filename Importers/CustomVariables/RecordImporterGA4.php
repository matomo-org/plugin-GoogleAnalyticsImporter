<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\Importers\CustomVariables;

use Piwik\Common;
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Date;
use Piwik\Metrics;
use Piwik\Plugins\CustomVariables\Archiver;
use Piwik\Plugins\CustomVariables\Model;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleAnalyticsGA4QueryService;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleGA4ResponseDataTableFactory;
use Piwik\Plugins\GoogleAnalyticsImporter\ImportConfiguration;
use Piwik\Log\LoggerInterface;
class RecordImporterGA4 extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporterGA4
{
    const PLUGIN_NAME = 'CustomVariables';
    protected $maximumRowsInDataTableLevelZero;
    protected $maximumRowsInSubDataTable;
    /**
     * @var ImportConfiguration
     */
    private $importConfiguration;
    /**
     * @var array
     */
    private $metadataFlat;
    public function __construct(GoogleAnalyticsGA4QueryService $gaQuery, $idSite, LoggerInterface $logger)
    {
        parent::__construct($gaQuery, $idSite, $logger);
        $this->maximumRowsInDataTableLevelZero = Config::getInstance()->General['datatable_archiving_maximum_rows_custom_variables'] ?? Config::getInstance()->General['datatable_archiving_maximum_rows_custom_dimensions'];
        $this->maximumRowsInSubDataTable = Config::getInstance()->General['datatable_archiving_maximum_rows_subtable_custom_variables'] ?? Config::getInstance()->General['datatable_archiving_maximum_rows_subtable_custom_dimensions'];
        $this->importConfiguration = StaticContainer::get(ImportConfiguration::class);
    }
    public function importRecords(Date $day)
    {
        $this->metadataFlat = [];
        $record = new DataTable();
        for ($i = 1; $i < $this->importConfiguration->getNumCustomVariables() + 1; ++$i) {
            $this->queryCustomVariableSlot($i, $day, $record);
        }
        $this->insertRecord(Archiver::CUSTOM_VARIABLE_RECORD_NAME, $record, $this->maximumRowsInDataTableLevelZero, $this->maximumRowsInSubDataTable, Metrics::INDEX_NB_VISITS);
        Common::destroy($record);
        unset($this->metadataFlat);
    }
    private function queryCustomVariableSlot($index, Date $day, DataTable $record)
    {
        //Do not query Just created an empty datatable and set NOT_AVAILABLE_IN_GA_LABEL so that people get to know it's not available in GA4
        $keyField = 'ga:customVarName' . $index;
        $valueField = 'ga:customVarValue' . $index;
        $dataTableFactory = new GoogleGA4ResponseDataTableFactory([], [], []);
        $table = $dataTableFactory->getDataTable();
        $this->processCustomVarQuery($record, $table, Model::SCOPE_VISIT, $keyField, $valueField);
        $this->processCustomVarQuery($record, $table, Model::SCOPE_PAGE, $keyField, $valueField);
        $this->processCustomVarQuery($record, $table, Model::SCOPE_CONVERSION, $keyField, $valueField);
        Common::destroy($table);
        /** Not available in GA4
                $gaQuery = $this->getGaClient();
                $table = $gaQuery->query($day, $dimensions = [$keyField, $valueField], $this->getVisitMetrics());
                $this->processCustomVarQuery($record, $table, Model::SCOPE_VISIT, $keyField, $valueField);
                Common::destroy($table);
        
                $table = $gaQuery->query($day, $dimensions = [$keyField, $valueField], $this->getActionMetrics());
                $this->processCustomVarQuery($record, $table, Model::SCOPE_PAGE, $keyField, $valueField);
                Common::destroy($table);
        
                $table = $gaQuery->query($day, $dimensions = [$keyField, $valueField], [Metrics::INDEX_GOALS]);
                $this->processCustomVarQuery($record, $table, Model::SCOPE_CONVERSION, $keyField, $valueField);
                Common::destroy($table);
        
                 */
    }
    private function processCustomVarQuery(DataTable $record, DataTable $table, $scope, $keyField, $valueField)
    {
        $table->addRowFromArray([Row::METADATA => [self::NOT_AVAILABLE_IN_GA_LABEL => 1]]);
        foreach ($table->getRows() as $row) {
            $key = $row->getMetadata($keyField);
            if (empty($key)) {
                $key = self::NOT_AVAILABLE_IN_GA_LABEL;
            }
            $value = $this->cleanValue($row->getMetadata($valueField));
            if (empty($value)) {
                $value = self::NOT_AVAILABLE_IN_GA_LABEL;
            }
            $this->addMetadata($keyField, $key, $scope, $row);
            $topLevelRow = $this->addRowToTable($record, $row, $key);
            $this->addRowToSubtable($topLevelRow, $row, $value);
        }
    }
    private function cleanValue($value)
    {
        if (strlen($value)) {
            return $value;
        }
        return Archiver::LABEL_CUSTOM_VALUE_NOT_DEFINED;
    }
    private function addMetadata($keyField, $label, $scope, DataTable\Row $row)
    {
        $index = (int) str_replace('custom_var_k', '', $keyField);
        $uniqueId = $label . 'scope' . $scope . 'index' . $index;
        if (isset($this->metadataFlat[$uniqueId])) {
            return;
        }
        $this->metadataFlat[$uniqueId] = \true;
        $slots = $row->getMetadata('slots') ?: [];
        $slots[] = ['scope' => $scope, 'index' => $index];
        $row->setMetadata('slots', $slots);
    }
}
