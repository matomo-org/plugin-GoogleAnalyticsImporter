<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\Importers\VisitTime;

use Piwik\Common;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Plugins\VisitTime\Archiver;
class RecordImporter extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporter
{
    const PLUGIN_NAME = 'VisitTime';
    public function importRecords(Date $day)
    {
        $this->queryDimension($day, 'ga:hour', Archiver::SERVER_TIME_RECORD_NAME);
    }
    private function queryDimension(Date $day, $dimension, $recordName)
    {
        $record = new DataTable();
        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, [$dimension], $this->getConversionAwareVisitMetrics());
        foreach ($table->getRows() as $row) {
            $label = $row->getMetadata($dimension);
            if (empty($label)) {
                $label = self::NOT_SET_IN_GA_LABEL;
            }
            $this->addRowToTable($record, $row, $label);
        }
        $this->insertRecord($recordName, $record);
        Common::destroy($record);
    }
}
