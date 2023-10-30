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
use Piwik\Container\StaticContainer;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Metrics;
use Piwik\Plugins\Goals\Archiver;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleAnalyticsQueryService;
use Piwik\Plugins\GoogleAnalyticsImporter\Importer;
use Piwik\Plugins\VisitFrequency\API as VisitFrequencyAPI;
use Piwik\Site;
use Piwik\Tracker\GoalManager;
use Piwik\Log\LoggerInterface;
class RecordImporter extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporter
{
    const PLUGIN_NAME = 'Goals';
    private $itemRecords;
    private $segmentToApply;
    public function __construct(GoogleAnalyticsQueryService $gaQuery, $idSite, LoggerInterface $logger, $segmentToApply = null)
    {
        parent::__construct($gaQuery, $idSite, $logger);
        $this->segmentToApply = $segmentToApply;
    }
    public function importRecords(Date $day)
    {
        $this->queryEcommerce($day);
        $this->queryNumericRecords($day);
    }
    private function queryNumericRecords(Date $day)
    {
        if ($this->segmentToApply !== null) {
            // if computing for new visit/returning visitor segment
            $this->queryNumericRecordsWithSegmentSet($day, $this->segmentToApply);
            return;
        }
        // if this is the main record importer instance, archive once for root segment
        $this->queryNumericRecordsWithSegmentSet($day, null);
        // then import for dependent segments
        $segments = [VisitFrequencyAPI::NEW_VISITOR_SEGMENT => ['segmentId' => 'gaid::-2'], VisitFrequencyAPI::RETURNING_VISITOR_SEGMENT => ['segmentId' => 'gaid::-3']];
        $site = new Site($this->getIdSite());
        $importer = StaticContainer::get(Importer::class);
        foreach ($segments as $segment => $gaSegment) {
            $childRecordImporter = new \Piwik\Plugins\GoogleAnalyticsImporter\Importers\Goals\RecordImporter($this->getGaQuery(), $this->getIdSite(), $this->getLogger(), $gaSegment);
            $importer->importDay($site, $day, ['Goals' => $childRecordImporter], $segment, 'Goals');
        }
    }
    private function queryNumericRecordsWithSegmentSet(Date $day, $segmentToApply)
    {
        $gaQuery = $this->getGaQuery();
        $options = [];
        if (!empty($segmentToApply)) {
            $options['segment'] = $segmentToApply;
        }
        $table = $gaQuery->query($day, $dimensions = [], $metrics = [Metrics::INDEX_NB_VISITS, Metrics::INDEX_GOALS], $options);
        if ($table->getRowsCount() == 0) {
            return;
        }
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
        $this->insertNumericRecords([Archiver::getRecordName('nb_conversions') => $table->getFirstRow()->getColumn(Metrics::INDEX_NB_CONVERSIONS), Archiver::getRecordName('nb_visits_converted') => $table->getFirstRow()->getColumn(Metrics::INDEX_NB_VISITS_CONVERTED), Archiver::getRecordName('revenue') => $table->getFirstRow()->getColumn(Metrics::INDEX_REVENUE)]);
        Common::destroy($table);
        $this->insertNumericRecords($numericRecords);
    }
    private function queryEcommerce(Date $day)
    {
        if (!Site::isEcommerceEnabledFor($this->getIdSite())) {
            return;
        }
        $this->itemRecords = [Archiver::ITEMS_SKU_RECORD_NAME => new DataTable(), Archiver::ITEMS_NAME_RECORD_NAME => new DataTable(), Archiver::ITEMS_CATEGORY_RECORD_NAME => new DataTable()];
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
        $table = $gaQuery->query($day, [$dimension], [Metrics::INDEX_NB_CONVERSIONS], ['mappings' => [Metrics::INDEX_NB_CONVERSIONS => 'ga:transactions'], 'orderBys' => [['field' => 'ga:transactions', 'order' => 'desc']]]);
        foreach ($table->getRows() as $row) {
            $label = $row->getMetadata($dimension);
            if ($label === \false) {
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
        $recordsAndDimensions = [Archiver::ITEMS_SKU_RECORD_NAME => 'ga:productSku', Archiver::ITEMS_NAME_RECORD_NAME => 'ga:productName', Archiver::ITEMS_CATEGORY_RECORD_NAME => 'ga:productCategory'];
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
