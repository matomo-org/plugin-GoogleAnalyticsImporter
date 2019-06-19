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

    public function queryGoogleAnalyticsApi(Date $day)
    {
        $this->queryDimension($day, 'ga:hour', Archiver::LOCAL_TIME_RECORD_NAME);
    }

    // TODO: definitely could put this method into the base class and reuse in a couple importers
    private function queryDimension(Date $day, $dimension, $recordName)
    {
        $record = new DataTable();

        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, [$dimension], $this->getConversionAwareVisitMetrics());
        foreach ($table->getRows() as $row) {
            $label = $row->getMetadata($dimension);
            $this->addRowToTable($record, $row, $label);
        }

        $this->insertRecord($recordName, $record);
        Common::destroy($record);
    }

    private function insertRecord($recordName, DataTable $record)
    {
        $blob = $record->getSerialized();
        $this->insertBlobRecord($recordName, $blob);
    }
}