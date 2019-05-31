<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\Importers\Referrers;


use Piwik\Config;
use Piwik\DataAccess\ArchiveWriter;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Metrics;
use Piwik\Plugins\GoogleAnalyticsImporter\GoogleAnalyticsQueryService;
use Piwik\Plugins\Referrers\Archiver;
use Piwik\Url;

// TODO: remove tracker related code

class RecordImporter extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporter
{
    const PLUGIN_NAME = 'Referrers';

    private $maximumRowsInDataTableLevelZero;
    private $maximumRowsInSubDataTable;
    private $columnToSortByBeforeTruncation;

    public function __construct(GoogleAnalyticsQueryService $gaQuery, $idSite)
    {
        parent::__construct($gaQuery, $idSite);

        // TODO: code redundancy w/ referrers
        $this->columnToSortByBeforeTruncation = Metrics::INDEX_NB_VISITS;

        // Reading pre 2.0 config file settings
        $this->maximumRowsInDataTableLevelZero = @Config::getInstance()->General['datatable_archiving_maximum_rows_referers'];
        $this->maximumRowsInSubDataTable = @Config::getInstance()->General['datatable_archiving_maximum_rows_subtable_referers'];
        if (empty($this->maximumRowsInDataTableLevelZero)) {
            $this->maximumRowsInDataTableLevelZero = Config::getInstance()->General['datatable_archiving_maximum_rows_referrers'];
            $this->maximumRowsInSubDataTable = Config::getInstance()->General['datatable_archiving_maximum_rows_subtable_referrers'];
        }
    }

    public function queryGoogleAnalyticsApi(Date $day)
    {
        /*$keywordBySearchEngine = $this->getKeywordsBySearchEngineRecord($day);
        $urlBySocialNetwork = $this->getUrlsBySocialNetwork($day);
        $searchEngineByKeyword = $this->getSearchEngineByKeyword($day);
        $keywordByCampaign = $this->getKeywordByCampaign($day);*/
        $urlByWebsite = $this->getUrlByWebsite($day);
        $blob = $urlByWebsite->getSerialized($this->maximumRowsInDataTableLevelZero, $this->maximumRowsInSubDataTable, $this->columnToSortByBeforeTruncation);
        $this->insertBlobRecord(Archiver::WEBSITES_RECORD_NAME, $blob);
    }

    private function getUrlByWebsite(Date $day)
    {
        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, $dimensions = ['ga:fullReferrer'], $this->getVisitMetrics());

        $record = new DataTable();
        foreach ($table->getRows() as $rowIndex => $row) {
            $referrerUrl = $row->getMetadata('ga:fullReferrer');
            $row->deleteMetadata('ga:fullReferrer');

            // URLs don't have protocols in GA
            $referrerUrl = 'http://' . $referrerUrl;

            // invalid rows for direct entries and search engines (TODO: check for more possibilities?)
            if ($referrerUrl == '(direct)'
                || strrpos($referrerUrl, '/') !== strlen($referrerUrl) - 1
            ) {
                continue;
            }

            $parsedUrl = @parse_url($referrerUrl);
            $host = isset($parsedUrl['host']) ? $parsedUrl['host'] : null;
            $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : null;

            $topLevelRow = $this->addRowToTable($record, $row, $host);

            $subtable = $topLevelRow->getSubtable();
            if (!$subtable) {
                $subtable = new DataTable();
                $topLevelRow->setSubtable($subtable);
            }

            $this->addRowToTable($subtable, $row, $path);
        }
        return $record;
    }

    private function addRowToTable(DataTable $record, DataTable\Row $row, $newLabel)
    {
        $foundRow = $record->getRowFromLabel($newLabel);
        if (empty($foundRow)) {
            $foundRow = clone $row;
            $foundRow->setColumn('label', $newLabel);
            $record->addRow($foundRow);
        } else {
            $foundRow->sumRow($row);
        }
        return $foundRow;
    }
}

/*
    const SEARCH_ENGINES_RECORD_NAME = 'Referrers_keywordBySearchEngine';
    const SOCIAL_NETWORKS_RECORD_NAME = 'Referrers_urlBySocialNetwork';
    const KEYWORDS_RECORD_NAME = 'Referrers_searchEngineByKeyword';
    const CAMPAIGNS_RECORD_NAME = 'Referrers_keywordByCampaign';
    const WEBSITES_RECORD_NAME = 'Referrers_urlByWebsite';
    const REFERRER_TYPE_RECORD_NAME = 'Referrers_type';

 */