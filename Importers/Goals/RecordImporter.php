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

class RecordImporter extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporter
{
    const PLUGIN_NAME = 'Goals';

    private $itemRecords;

    public function queryGoogleAnalyticsApi(Date $day)
    {
        $this->queryEcommerce($day);
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
            $this->insertBlobRecord($recordName, $record->getSerialized());
            Common::destroy($record);
        }

        unset($this->itemRecords);
    }

    private function queryVisitsUntilTransaction(Date $day)
    {
        $this->queryConversionsDimension($day, 'ga:sessionsToTransaction', Archiver::VISITS_UNTIL_RECORD_NAME);
    }

    private function queryDaysUntilTransaction(Date $day)
    {
        $this->queryConversionsDimension($day, 'ga:daysToTransaction', Archiver::DAYS_UNTIL_CONV_RECORD_NAME);
    }

    private function queryConversionsDimension(Date $day, $dimension, $recordName)
    {
        $record = new DataTable();

        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, [$dimension], $this->getConversionOnlyMetrics(), [
            'mappings' => $gaQuery->getEcommerceMetricIndicesToGaMetrics(),
        ]);

        foreach ($table->getRows() as $row) {
            $label = $row->getMetadata($dimension);

            // TODO: would be better to support this in GoogleAnalyticsQueryService, but there's no way to tell INDEX_GOAL_REVENUE is for
            // the ecommerce goal...
            $totalRevenue = $row->getColumn(Metrics::INDEX_GOAL_ECOMMERCE_REVENUE_SHIPPING)
                + $row->getColumn(Metrics::INDEX_GOAL_ECOMMERCE_REVENUE_SUBTOTAL)
                + $row->getColumn(Metrics::INDEX_GOAL_ECOMMERCE_REVENUE_TAX);
            $row->setColumn(Metrics::INDEX_GOAL_REVENUE, $totalRevenue);

            $this->addRowToTable($record, $row, $label);
        }

        $this->insertBlobRecord($recordName, $record->getSerialized());
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
                $this->addRowToTable($this->itemRecords[$recordName], $row, $label);
            }

            Common::destroy($table);
        }
    }
}
