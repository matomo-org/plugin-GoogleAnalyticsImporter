<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\Importers\MarketingCampaignsReporting;

use Piwik\Common;
use Piwik\Config;
use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Date;
use Piwik\Metrics;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleAnalyticsGA4QueryService;
use Piwik\Plugins\MarketingCampaignsReporting\Archiver;
use Piwik\Log\LoggerInterface;
class RecordImporterGA4 extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporterGA4
{
    const PLUGIN_NAME = 'MarketingCampaignsReporting';
    private $records = [];
    protected $columnToSortByBeforeTruncation;
    protected $maximumRowsInDataTable;
    protected $maximumRowsInSubDataTable;
    public function __construct(GoogleAnalyticsGA4QueryService $gaQuery, $idSite, LoggerInterface $logger, $segmentToApply = null)
    {
        parent::__construct($gaQuery, $idSite, $logger);
        $this->columnToSortByBeforeTruncation = Metrics::INDEX_NB_VISITS;
        $this->maximumRowsInDataTable = Config::getInstance()->General['datatable_archiving_maximum_rows_referrers'];
        $this->maximumRowsInSubDataTable = Config::getInstance()->General['datatable_archiving_maximum_rows_subtable_referrers'];
    }
    public function importRecords(Date $day)
    {
        $this->initDataArrays();
        $gaQuery = $this->getGaClient();
        $dimensions = $this->getDimensionsToQuery();
        $table = $gaQuery->query($day, $dimensions, $this->getConversionAwareVisitMetrics());
        $recordsToDimensions = self::getRecordToDimensions();
        foreach ($table->getRows() as $row) {
            $campaignName = $row->getMetadata('sessionCampaignName');
            if (empty($campaignName)) {
                continue;
            }
            foreach ($recordsToDimensions as $recordName => $dimensionsForRecord) {
                $record = $this->getRecord($recordName);
                $mainLabelDimensions = $dimensionsForRecord[0];
                $mainLabel = $this->getLabelFromRowDimensions($mainLabelDimensions, $row);
                if (empty($mainLabel)) {
                    continue 1;
                }
                $addedRow = $this->addRowToTable($record, $row, $mainLabel);
                if (isset($dimensionsForRecord[1])) {
                    $subLabelDimensions = $dimensionsForRecord[1];
                    $subLabel = $this->getLabelFromRowDimensions($subLabelDimensions, $row);
                    if (empty($subLabel)) {
                        continue 1;
                    }
                    $this->addRowToSubtable($addedRow, $row, $subLabel);
                }
            }
        }
        foreach ($this->records as $recordName => $table) {
            $this->insertRecord($recordName, $table, $this->maximumRowsInDataTable, $this->maximumRowsInSubDataTable, $this->columnToSortByBeforeTruncation);
        }
        Common::destroy($table);
        foreach ($this->records as $record) {
            Common::destroy($record);
        }
        unset($this->records);
    }
    protected function initDataArrays()
    {
        foreach (self::getRecordToDimensions() as $recordName => $ignore) {
            $this->records[$recordName] = new DataTable();
        }
    }
    /**
     * @param string $name
     * @return DataTable
     */
    protected function getRecord($recordName)
    {
        return $this->records[$recordName];
    }
    /**
     * @param $dimensionsAsLabel
     * @param Row $row
     * @return string
     */
    protected function getLabelFromRowDimensions($dimensionsAsLabel, $row)
    {
        $labels = array();
        foreach ($dimensionsAsLabel as $dimensionLabelPart) {
            $part = $row->getMetadata($dimensionLabelPart);
            if (isset($part) && $part != '') {
                $labels[] = $part;
            }
        }
        $label = implode(Archiver::SEPARATOR_COMBINED_DIMENSIONS, $labels);
        return $label;
    }
    /**
     * Backup in case of plugin version mismatch.
     */
    public static function getRecordToDimensions()
    {
        return array(Archiver::CAMPAIGN_NAME_RECORD_NAME => array(array("sessionCampaignName"), array("sessionGoogleAdsKeyword", "sessionManualAdContent")), Archiver::CAMPAIGN_KEYWORD_RECORD_NAME => array(array("sessionGoogleAdsKeyword")), Archiver::CAMPAIGN_ID_RECORD_NAME => array(array("sessionCampaignId")), Archiver::CAMPAIGN_SOURCE_RECORD_NAME => array(array("sessionSource")), Archiver::CAMPAIGN_MEDIUM_RECORD_NAME => array(array("sessionMedium")), Archiver::CAMPAIGN_CONTENT_RECORD_NAME => array(array("sessionManualAdContent")), Archiver::HIERARCHICAL_SOURCE_MEDIUM_RECORD_NAME => array(array("sessionSource", "sessionMedium"), array("sessionCampaignName")));
    }
    private function getDimensionsToQuery()
    {
        return ['sessionCampaignId', 'sessionCampaignName', 'sessionGoogleAdsKeyword', 'sessionSource', 'sessionMedium', 'sessionManualAdContent'];
    }
}
