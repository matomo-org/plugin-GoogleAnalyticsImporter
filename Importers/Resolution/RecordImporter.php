<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\Importers\Resolution;

use Piwik\Common;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Metrics;
use Piwik\Plugins\Resolution\Archiver;
class RecordImporter extends \Piwik\Plugins\GoogleAnalyticsImporter\Importers\DevicesDetection\RecordImporter
{
    const PLUGIN_NAME = 'Resolution';
    public function importRecords(Date $day)
    {
        $this->queryScreenResolution($day);
        $this->queryConfig($day);
    }
    private function queryConfig(Date $day)
    {
        $record = new DataTable();
        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, $dimension = ['ga:operatingSystem', 'ga:browser', 'ga:screenResolution'], $this->getConversionAwareVisitMetrics());
        foreach ($table->getRows() as $row) {
            $screenResolution = $row->getMetadata('ga:screenResolution');
            if (empty($screenResolution)) {
                $screenResolution = '(not set)';
            }
            $browser = $this->mapBrowser($row->getMetadata('ga:browser'));
            if (empty($browser)) {
                $browser = 'xx';
            }
            $operatingSystem = $this->mapOs($row->getMetadata('ga:operatingSystem'));
            if (empty($operatingSystem)) {
                $operatingSystem = 'xx';
            }
            $label = $operatingSystem . ';' . $browser . ';' . $screenResolution;
            $this->addRowToTable($record, $row, $label);
        }
        Common::destroy($table);
        $this->insertRecord(Archiver::CONFIGURATION_RECORD_NAME, $record, $this->getStandardMaximumRows(), null, Metrics::INDEX_NB_VISITS);
        unset($blob);
        Common::destroy($record);
    }
    private function queryScreenResolution(Date $day)
    {
        $record = new DataTable();
        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, $dimension = ['ga:screenResolution'], $this->getConversionAwareVisitMetrics());
        foreach ($table->getRows() as $row) {
            $label = $row->getMetadata('ga:screenResolution');
            if (empty($label)) {
                $label = self::NOT_SET_IN_GA_LABEL;
            }
            $this->addRowToTable($record, $row, $label);
        }
        Common::destroy($table);
        $this->insertRecord(Archiver::RESOLUTION_RECORD_NAME, $record, $this->getStandardMaximumRows(), null, Metrics::INDEX_NB_VISITS);
        unset($blob);
        Common::destroy($record);
    }
}
