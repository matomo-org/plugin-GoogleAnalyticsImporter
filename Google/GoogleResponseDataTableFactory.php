<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\Google;

use Piwik\DataTable;
use Piwik\DataTable\Row;
class GoogleResponseDataTableFactory
{
    /**
     * @var DataTable
     */
    private $dataTable;
    private $dimensions;
    private $metricIndices;
    private $gaMetrics;
    private $defaultRow;
    public function __construct($dimensions, $metricIndices, $gaMetrics)
    {
        $this->dataTable = new DataTable();
        $this->dimensions = $dimensions;
        $this->metricIndices = $metricIndices;
        $this->gaMetrics = $gaMetrics;
        $this->defaultRow = $this->makeDefaultRow();
    }
    public function getDataTable()
    {
        return $this->dataTable;
    }
    /**
     * For tests.
     * @ignore
     */
    public function setDataTable(DataTable $table)
    {
        $this->dataTable = $table;
    }
    public function mergeGaResponse(\Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\AnalyticsReporting\GetReportsResponse $response, array $chunk)
    {
        /** @var \Google\Service\AnalyticsReporting\Report $gaReport */
        foreach ($response->getReports() as $gaReport) {
            /** @var \Google\Service\AnalyticsReporting\ReportRow $gaRow */
            foreach ($gaReport->getData()->getRows() as $gaRow) {
                $tableRow = clone $this->defaultRow;
                // convert GA row which is just array of values w/ integer indexes to matomo row
                // mapping GA metric names => values
                $gaRowMetrics = $gaRow->getMetrics()[0]->getValues();
                foreach (array_values($chunk) as $index => $metricName) {
                    $tableRow->setColumn($metricName, $gaRowMetrics[$index]);
                }
                // gather all dimensions to create the label column (we need to be able to find existing rows from dimensions
                // so we combine these dimensions into a single label)
                $label = [];
                foreach (array_values($this->dimensions) as $index => $dimension) {
                    $labelValue = $gaRow->dimensions[$index] == '(not set)' ? null : $gaRow->dimensions[$index];
                    $tableRow->setMetadata($dimension, $labelValue);
                    $label[$dimension] = $labelValue;
                }
                if (!empty($label)) {
                    $label = implode(',', $label);
                    // so we can call getRowFromLabel()
                    $tableRow->setColumn('label', $label);
                }
                $existingRow = empty($this->dimensions) ? $this->dataTable->getFirstRow() : $this->dataTable->getRowFromLabel($label);
                if (!empty($existingRow)) {
                    $existingRow->sumRow($tableRow);
                } else {
                    $this->dataTable->addRow($tableRow);
                }
            }
        }
    }
    public function convertGaColumnsToMetricIndexes($mappings)
    {
        $metricNames = [];
        foreach ($this->metricIndices as $metricIndex) {
            if (is_array($mappings[$metricIndex])) {
                $metricInfo = $mappings[$metricIndex];
                if (is_array($metricInfo['metric'])) {
                    $metricNames[$metricIndex] = $metricInfo['metric'][0];
                } else {
                    $metricNames[$metricIndex] = $metricInfo['metric'];
                }
            } else {
                $metricNames[$metricIndex] = $mappings[$metricIndex];
            }
        }
        foreach ($this->dataTable->getRows() as $row) {
            $newColumns = ['label' => $row->getColumn('label')];
            foreach ($this->metricIndices as $metricIndex) {
                $gaMetricName = $metricNames[$metricIndex];
                $value = $row->getColumn($gaMetricName);
                if ($value !== \false && isset($mappings[$metricIndex]['calculate'])) {
                    $fn = $mappings[$metricIndex]['calculate'];
                    $value = $fn($row);
                }
                if ($value !== \false) {
                    $newColumns[$metricIndex] = $value;
                }
            }
            $row->setColumns($newColumns);
        }
        $this->dataTable->setLabelsHaveChanged();
    }
    private function makeDefaultRow()
    {
        $defaultRow = new Row();
        foreach ($this->gaMetrics as $name) {
            $defaultRow->setColumn($name, 0);
        }
        return $defaultRow;
    }
}
