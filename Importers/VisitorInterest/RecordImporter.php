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

    public function importRecords(Date $day)
    {
        $this->queryDimension($day, 'ga:sessionCount', Archiver::VISITS_COUNT_RECORD_NAME);
        $this->queryDimension($day, 'ga:daysSinceLastSession', Archiver::DAYS_SINCE_LAST_RECORD_NAME);
        $this->queryVisitsByDuration($day);
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

    private function queryVisitsByDuration(Date $day)
    {
        $gap = Archiver::getSecondsGap();

        $record = new DataTable();

        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, ['ga:sessionDurationBucket'], $this->getConversionAwareVisitMetrics());
        foreach ($table->getRows() as $row) {
            $durationInSecs = $row->getMetadata('ga:sessionDurationBucket');
            $label = $this->getDurationGapLabel($gap, $durationInSecs);

            $this->addRowToTable($record, $row, $label);
        }

        $this->insertRecord(Archiver::TIME_SPENT_RECORD_NAME, $record);

        Common::destroy($record);
    }

    private function insertRecord($recordName, DataTable $record)
    {
        $blob = $record->getSerialized();
        $this->insertBlobRecord($recordName, $blob);
    }

    private function getDurationGapLabel(array $gap, $durationInSecs)
    {
        $range = null;

        foreach ($gap as $bounds) {
            $upperBound = end($bounds);
            if ($durationInSecs <= $upperBound) {
                $range = reset($bounds) . ' - ' . $upperBound;
                break;
            }
        }

        if (empty($range)) {
            $lowerBound = reset($bounds);
            $range = ($lowerBound + 1) . urlencode('+');
        }

        return $range;
    }
}