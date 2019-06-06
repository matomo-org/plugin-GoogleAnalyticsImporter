<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\Importers\Referrers;


use Piwik\Common;
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\DataAccess\ArchiveWriter;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Metrics;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\SearchEngineMapper;
use Piwik\Plugins\GoogleAnalyticsImporter\GoogleAnalyticsQueryService;
use Piwik\Plugins\Referrers\Archiver;
use Piwik\Plugins\Referrers\SearchEngine;
use Piwik\Url;

// TODO: remove tracker related code

class RecordImporter extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporter
{
    const PLUGIN_NAME = 'Referrers';

    private $maximumRowsInDataTableLevelZero;
    private $maximumRowsInSubDataTable;
    private $columnToSortByBeforeTruncation;

    /**
     * @var SearchEngineMapper
     */
    private $searchEngineMapper;

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

        $this->searchEngineMapper = StaticContainer::get(SearchEngineMapper::class);
    }

    public function queryGoogleAnalyticsApi(Date $day)
    {
        /*
        $urlBySocialNetwork = $this->getUrlsBySocialNetwork($day);
        $keywordByCampaign = $this->getKeywordByCampaign($day);*/

        list($keywordBySearchEngine, $searchEngineByKeyword) = $this->getKeywordsAndSearchEngineRecords($day);

        $blob = $keywordBySearchEngine->getSerialized($this->maximumRowsInDataTableLevelZero, $this->maximumRowsInSubDataTable, $this->columnToSortByBeforeTruncation);
        $this->insertBlobRecord(Archiver::KEYWORDS_RECORD_NAME, $blob);
        Common::destroy($keywordBySearchEngine);

        $blob = $searchEngineByKeyword->getSerialized($this->maximumRowsInDataTableLevelZero, $this->maximumRowsInSubDataTable, $this->columnToSortByBeforeTruncation);
        $this->insertBlobRecord(Archiver::SEARCH_ENGINES_RECORD_NAME, $blob);
        Common::destroy($searchEngineByKeyword);

        $urlByWebsite = $this->getUrlByWebsite($day);
        $blob = $urlByWebsite->getSerialized($this->maximumRowsInDataTableLevelZero, $this->maximumRowsInSubDataTable, $this->columnToSortByBeforeTruncation);
        $this->insertBlobRecord(Archiver::WEBSITES_RECORD_NAME, $blob);
        Common::destroy($urlByWebsite);

        unset($blob);
    }

    private function getUrlByWebsite(Date $day)
    {
        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, $dimensions = ['ga:fullReferrer'], $this->getConversionAwareVisitMetrics());

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

        Common::destroy($table);

        return $record;
    }

    private function getKeywordsAndSearchEngineRecords(Date $day)
    {
        $keywordBySearchEngine = new DataTable();
        $searchEngineByKeyword = new DataTable();

        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, $dimensions = ['ga:source', 'ga:medium', 'ga:keyword'], $this->getConversionAwareVisitMetrics());

        foreach ($table->getRows() as $row) {
            $source = $row->getMetadata('ga:source');
            $medium = $row->getMetadata('ga:medium');
            $keyword = $row->getMetadata('ga:keyword');

            if ($medium == 'referral') {
                $searchEngineName = $this->searchEngineMapper->mapReferralMediumToSearchEngine($medium);
            } else if ($medium == 'organic') { // not a search engine referrer
                $searchEngineName = $this->searchEngineMapper->mapSourceToSearchEngine($source);
            }

            if (!isset($searchEngineName)) {
                continue;
            }

            if (empty($keyword)) {
                $keyword = '(not provided)';
            }

            $row->deleteMetadata();

            // add to keyword by search engine record
            $topLevelRow = $this->addRowToTable($keywordBySearchEngine, $row, $keyword);
            $subtable = $topLevelRow->getSubtable();
            if (!$subtable) {
                $subtable = new DataTable();
                $topLevelRow->setSubtable($subtable);
            }
            $this->addRowToTable($subtable, $row, $searchEngineName);

            // add to search engine by keyword record
            $topLevelRow = $this->addRowToTable($searchEngineByKeyword, $row, $searchEngineName);
            $subtable = $topLevelRow->getSubtable();
            if (!$subtable) {
                $subtable = new DataTable();
                $topLevelRow->setSubtable($subtable);
            }
            $this->addRowToTable($subtable, $row, $keyword);
        }

        Common::destroy($table);

        return [$keywordBySearchEngine, $searchEngineByKeyword];
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