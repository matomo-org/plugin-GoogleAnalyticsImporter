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
use Piwik\Date;
use Piwik\Plugins\VisitorInterest\Archiver;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleGA4ResponseDataTableFactory;
use Piwik\DataTable\Row;
class RecordImporterGA4 extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporterGA4
{
    const PLUGIN_NAME = 'VisitorInterest';
    private $secondsGap;
    public function importRecords(Date $day)
    {
        $this->secondsGap = Archiver::getSecondsGap();
        $this->queryDimension($day, 'ga:pageDepth', Archiver::$pageGap, Archiver::PAGES_VIEWED_RECORD_NAME, function ($value) {
            return $this->getPagesViewedLabel($value);
        });
        $this->queryDimension($day, 'ga:sessionCount', Archiver::$visitNumberGap, Archiver::VISITS_COUNT_RECORD_NAME, function ($value) {
            return $this->getVisitByNumberLabel($value);
        });
        $this->queryDimension($day, 'ga:daysSinceLastSession', Archiver::$daysSinceLastVisitGap, Archiver::DAYS_SINCE_LAST_RECORD_NAME, function ($value) {
            return $this->getVisitsByDaysSinceLastLabel($value);
        });
        $this->queryVisitsByDuration($day);
    }
    private function queryDimension(Date $day, $dimension, $gap, $recordName, $labelMapper)
    {
        $record = $this->createTableFromGap($gap);
        $dataTableFactory = new GoogleGA4ResponseDataTableFactory([], [], []);
        $table = $dataTableFactory->getDataTable();
        /* No need to query since not available in GA4, only set NOT_AVAILABLE_IN_GA_LABEL to notify not available in GA4
           $gaQuery = $this->getGaClient();
           $table = $gaQuery->query($day, [$dimension], $this->getConversionAwareVisitMetrics());
           */
        $table->addRowFromArray([Row::METADATA => [self::NOT_AVAILABLE_IN_GA_LABEL => 1]]);
        foreach ($table->getRows() as $row) {
            $label = $row->getMetadata($dimension);
            $label = $labelMapper($label);
            $this->addRowToTable($record, $row, $label);
        }
        $this->insertRecord($recordName, $record);
        Common::destroy($record);
    }
    private function queryVisitsByDuration(Date $day)
    {
        $record = $this->createTableFromGap($this->secondsGap);
        $dataTableFactory = new GoogleGA4ResponseDataTableFactory([], [], []);
        $table = $dataTableFactory->getDataTable();
        $table->addRowFromArray([Row::METADATA => [self::NOT_AVAILABLE_IN_GA_LABEL => 1]]);
        /* No need to query since not available in GA4, only set NOT_AVAILABLE_IN_GA_LABEL to notify not available in GA4
           $gaQuery = $this->getGaClient();
           $table = $gaQuery->query($day, ['ga:sessionDurationBucket'], $this->getConversionAwareVisitMetrics());
           */
        foreach ($table->getRows() as $row) {
            //            $durationInSecs = $row->getMetadata('ga:sessionDurationBucket');
            //            $label = $this->getDurationGapLabel($durationInSecs);
            $label = self::NOT_AVAILABLE_IN_GA_LABEL;
            $this->addRowToTable($record, $row, $label);
        }
        $this->insertRecord(Archiver::TIME_SPENT_RECORD_NAME, $record);
        Common::destroy($record);
    }
    private function getPagesViewedLabel($value)
    {
        return $this->getGapLabel(Archiver::$pageGap, $value);
    }
    private function getVisitByNumberLabel($value)
    {
        return $this->getGapLabel(Archiver::$visitNumberGap, $value);
    }
    private function getVisitsByDaysSinceLastLabel($value)
    {
        return $this->getGapLabel(Archiver::$daysSinceLastVisitGap, $value);
    }
    private function getDurationGapLabel($value)
    {
        return $this->getGapLabel($this->secondsGap, $value);
    }
}
