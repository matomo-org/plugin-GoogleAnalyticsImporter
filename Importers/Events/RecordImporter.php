<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\Importers\Events;


use Piwik\Common;
use Piwik\Config;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Metrics;
use Piwik\Plugins\Events\Archiver;
use Piwik\Plugins\GoogleAnalyticsImporter\GoogleAnalyticsQueryService;
use Psr\Log\LoggerInterface;

class RecordImporter extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporter
{
    const PLUGIN_NAME = 'Events';

    private $maximumRowsInDataTable;
    private $maximumRowsInSubDataTable;

    private $records;

    public function __construct(GoogleAnalyticsQueryService $gaQuery, $idSite, LoggerInterface $logger)
    {
        parent::__construct($gaQuery, $idSite, $logger);

        $this->maximumRowsInDataTable = Config::getInstance()->General['datatable_archiving_maximum_rows_events'];
        $this->maximumRowsInSubDataTable = Config::getInstance()->General['datatable_archiving_maximum_rows_subtable_events'];
    }

    public function importRecords(Date $day)
    {
        $this->records = [
            Archiver::EVENTS_ACTION_CATEGORY_RECORD_NAME => new DataTable(),
            Archiver::EVENTS_ACTION_NAME_RECORD_NAME => new DataTable(),
            Archiver::EVENTS_CATEGORY_ACTION_RECORD_NAME => new DataTable(),
            Archiver::EVENTS_CATEGORY_NAME_RECORD_NAME => new DataTable(),
            Archiver::EVENTS_NAME_ACTION_RECORD_NAME => new DataTable(),
            Archiver::EVENTS_NAME_CATEGORY_RECORD_NAME => new DataTable(),
        ];

        $this->queryEvents($day);

        foreach ($this->records as $recordName => $record) {
            $blob = $record->getSerialized($this->maximumRowsInDataTable, $this->maximumRowsInSubDataTable, Metrics::INDEX_NB_VISITS);
            $this->insertBlobRecord($recordName, $blob);

            unset($blob);
            Common::destroy($record);
        }

        unset($this->records);
    }

    private function queryEvents(Date $day)
    {
        $metrics = array_merge($this->getConversionAwareVisitMetrics(), [
            Metrics::INDEX_EVENT_NB_HITS,
            Metrics::INDEX_EVENT_SUM_EVENT_VALUE,
        ]);

        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, $dimensions = ['ga:eventCategory', 'ga:eventAction', 'ga:eventLabel'], $metrics);

        foreach ($table->getRows() as $row) {
            $eventCategory = $row->getMetadata('ga:eventCategory');
            $eventAction = $row->getMetadata('ga:eventAction');
            $eventLabel = $row->getMetadata('ga:eventLabel');

            $row->deleteMetadata();

            $this->addRowToTables($this->records[Archiver::EVENTS_CATEGORY_ACTION_RECORD_NAME], $row, $eventCategory, $eventAction);
            $this->addRowToTables($this->records[Archiver::EVENTS_CATEGORY_NAME_RECORD_NAME], $row, $eventCategory, $eventLabel);

            $this->addRowToTables($this->records[Archiver::EVENTS_ACTION_CATEGORY_RECORD_NAME], $row, $eventAction, $eventCategory);
            $this->addRowToTables($this->records[Archiver::EVENTS_ACTION_NAME_RECORD_NAME], $row, $eventAction, $eventLabel);

            $this->addRowToTables($this->records[Archiver::EVENTS_NAME_ACTION_RECORD_NAME], $row, $eventLabel, $eventAction);
            $this->addRowToTables($this->records[Archiver::EVENTS_NAME_CATEGORY_RECORD_NAME], $row, $eventLabel, $eventCategory);
        }

        Common::destroy($table);
    }

    private function addRowToTables(DataTable $table, DataTable\Row $row, $topLevelLabel, $subTableLabel)
    {
        $topLevelRow = $this->addRowToTable($table, $row, $topLevelLabel);
        $this->addRowToSubtable($topLevelRow, $row, $subTableLabel);
    }
}
