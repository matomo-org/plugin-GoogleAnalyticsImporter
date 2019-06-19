<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\Importers\VisitorInterest;


use Piwik\Common;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Plugins\VisitorInterest\Archiver;

class RecordImporter extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporter
{
    const PLUGIN_NAME = 'VisitorInterest';

    public function queryGoogleAnalyticsApi(Date $day)
    {
        $this->queryDimension($day, 'ga:sessionCount', Archiver::VISITS_COUNT_RECORD_NAME);
        $this->queryDimension($day, 'ga:daysSinceLastSession', Archiver::DAYS_SINCE_LAST_RECORD_NAME);
    }

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