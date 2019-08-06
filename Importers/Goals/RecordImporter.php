<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\Importers\Goals;

use Piwik\Common;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Metrics;
use Piwik\Plugins\Goals\Archiver;
use Piwik\Plugins\SEO\Metric\Metric;
use Piwik\Site;
use Piwik\Tracker\GoalManager;

class RecordImporter extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporter
{
    const PLUGIN_NAME = 'Goals';

    private $itemRecords;

    public function importRecords(Date $day)
    {
        $this->queryEcommerce($day);
        $this->queryNumericRecords($day);
    }

    private function queryNumericRecords(Date $day)
    {
        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, $dimensions = [], $metrics = [Metrics::INDEX_GOALS]);

        $numericRecords = [];

        $goals = $table->getFirstRow()->getColumn(Metrics::INDEX_GOALS);
        if (!empty($goals)) {
            foreach ($goals as $idGoal => $metrics) {
                foreach ($metrics as $metricId => $value) {
                    $metricName = Metrics::$mappingFromIdToNameGoal[$metricId];
                    $recordName = Archiver::getRecordName($metricName, $idGoal);
                    $numericRecords[$recordName] = $value;
                }
            }
        }

        Common::destroy($table);

        $table = $gaQuery->query($day, $dimensions = [], [Metrics::INDEX_NB_VISITS_CONVERTED, Metrics::INDEX_NB_CONVERSIONS, Metrics::INDEX_REVENUE]);

        $this->insertNumericRecords([
            Archiver::getRecordName('nb_conversions') => $table->getFirstRow()->getColumn(Metrics::INDEX_NB_CONVERSIONS),
            Archiver::getRecordName('nb_visits_converted') => $table->getFirstRow()->getColumn(Metrics::INDEX_NB_VISITS_CONVERTED),
            Archiver::getRecordName('revenue') => $table->getFirstRow()->getColumn(Metrics::INDEX_REVENUE),
        ]);

        Common::destroy($table);

        $this->insertNumericRecords($numericRecords);
    }

    private function queryEcommerce(Date $day)
    {
        $this->itemRecords = [
            Archiver::ITEMS_SKU_RECORD_NAME => new DataTable(),
            Archiver::ITEMS_NAME_RECORD_NAME => new DataTable(),
            Archiver::ITEMS_CATEGORY_RECORD_NAME => new DataTable(),
        ];

        $this->queryVisitsUntilTransaction($day);
        $this->queryDaysUntilTransaction($day);
        $this->queryItemReports($day);

        foreach ($this->itemRecords as $recordName => &$record) {
            $this->insertRecord($recordName, $record);
            Common::destroy($record);
        }

        unset($this->itemRecords);
    }

    private function queryVisitsUntilTransaction(Date $day)
    {
        $this->queryXUntilConversionsDimension($day, 'ga:sessionsToTransaction', Archiver::getRecordName(Archiver::VISITS_UNTIL_RECORD_NAME, GoalManager::IDGOAL_ORDER), Archiver::$visitCountRanges);
    }

    private function queryDaysUntilTransaction(Date $day)
    {
        $this->queryXUntilConversionsDimension($day, 'ga:daysToTransaction', Archiver::getRecordName(Archiver::DAYS_UNTIL_CONV_RECORD_NAME, GoalManager::IDGOAL_ORDER), Archiver::$daysToConvRanges);
    }

    private function queryXUntilConversionsDimension(Date $day, $dimension, $recordName, $gap)
    {
        $record = $this->createTableFromGap($gap);

        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, [$dimension], [Metrics::INDEX_NB_CONVERSIONS], [
            'mappings' => [Metrics::INDEX_NB_CONVERSIONS => 'ga:transactions'],
        ]);

        foreach ($table->getRows() as $row) {
            $label = $row->getMetadata($dimension);
            if ($label === false) {
                $label = self::NOT_SET_IN_GA_LABEL;
            } else {
                $label = $this->getGapLabel($gap, $label);
            }

            $this->addRowToTable($record, $row, $label);
        }

        $this->insertRecord($recordName, $record);
        Common::destroy($record);
    }

    private function queryItemReports(Date $day)
    {
        $recordsAndDimensions = [
            Archiver::ITEMS_SKU_RECORD_NAME => 'ga:productSku',
            Archiver::ITEMS_NAME_RECORD_NAME => 'ga:productName',
            Archiver::ITEMS_CATEGORY_RECORD_NAME => 'ga:productCategory',
        ];

        $gaQuery = $this->getGaQuery();
        foreach ($recordsAndDimensions as $recordName => $dimensionName) {
            $table = $gaQuery->query($day, [$dimensionName], $this->getEcommerceMetrics());

            foreach ($table->getRows() as $row) {
                $label = $row->getMetadata($dimensionName);
                if (empty($label)) {
                    $label = self::NOT_SET_IN_GA_LABEL;
                }
                $this->addRowToTable($this->itemRecords[$recordName], $row, $label);
            }

            Common::destroy($table);
        }
    }
}
