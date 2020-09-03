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
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Metrics;
use Piwik\Piwik;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\SearchEngineMapper;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleAnalyticsQueryService;
use Piwik\Plugins\Referrers\Archiver;
use Piwik\Plugins\Referrers\Social;
use Psr\Log\LoggerInterface;

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

    /**
     * @var DataTable|null
     */
    private $referrerTypeRecord;

    private $campaignKeywords;

    public function __construct(GoogleAnalyticsQueryService $gaQuery, $idSite, LoggerInterface $logger)
    {
        parent::__construct($gaQuery, $idSite, $logger);

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

    public function importRecords(Date $day)
    {
        $this->campaignKeywords = [];

        $this->referrerTypeRecord = new DataTable();

        $keywordByCampaign = $this->getKeywordByCampaign($day);
        $distinctCampaigns = $keywordByCampaign->getRowsCount();

        $this->insertRecord(Archiver::CAMPAIGNS_RECORD_NAME, $keywordByCampaign, $this->maximumRowsInDataTableLevelZero, $this->maximumRowsInSubDataTable,
            $this->columnToSortByBeforeTruncation);
        Common::destroy($keywordByCampaign);

        list($keywordBySearchEngine, $searchEngineByKeyword) = $this->getKeywordsAndSearchEngineRecords($day);

        $distinctKeywords = $searchEngineByKeyword->getRowsCount();

        $this->insertRecord(Archiver::KEYWORDS_RECORD_NAME, $keywordBySearchEngine, $this->maximumRowsInDataTableLevelZero, $this->maximumRowsInSubDataTable, $this->columnToSortByBeforeTruncation);
        Common::destroy($keywordBySearchEngine);

        $distinctSearchEngines = $searchEngineByKeyword->getRowsCount();

        $this->insertRecord(Archiver::SEARCH_ENGINES_RECORD_NAME, $searchEngineByKeyword, $this->maximumRowsInDataTableLevelZero, $this->maximumRowsInSubDataTable, $this->columnToSortByBeforeTruncation);
        Common::destroy($searchEngineByKeyword);

        list($urlByWebsite, $urlBySocialNetwork) = $this->getUrlByWebsite($day);

        $distinctWebsites = $urlByWebsite->getRowsCount();

        $this->insertRecord(Archiver::WEBSITES_RECORD_NAME, $urlByWebsite, $this->maximumRowsInDataTableLevelZero, $this->maximumRowsInSubDataTable, $this->columnToSortByBeforeTruncation);
        Common::destroy($urlByWebsite);

        $distinctSocialNetworks = $urlBySocialNetwork->getRowsCount();

        $this->insertRecord(Archiver::SOCIAL_NETWORKS_RECORD_NAME, $urlBySocialNetwork, $this->maximumRowsInDataTableLevelZero, $this->maximumRowsInSubDataTable, $this->columnToSortByBeforeTruncation);
        Common::destroy($urlBySocialNetwork);

        $this->queryNumberOfDirectEntries($day);

        $this->insertRecord(Archiver::REFERRER_TYPE_RECORD_NAME, $this->referrerTypeRecord, $this->maximumRowsInDataTableLevelZero, $this->maximumRowsInSubDataTable, $this->columnToSortByBeforeTruncation);
        Common::destroy($this->referrerTypeRecord);
        $this->referrerTypeRecord = null;

        unset($blob);
        unset($this->campaignKeywords);

        // numeric records
        $numericRecords = array(
            Archiver::METRIC_DISTINCT_SEARCH_ENGINE_RECORD_NAME  => $distinctSearchEngines,
            Archiver::METRIC_DISTINCT_SOCIAL_NETWORK_RECORD_NAME => $distinctSocialNetworks,
            Archiver::METRIC_DISTINCT_KEYWORD_RECORD_NAME        => $distinctKeywords,
            Archiver::METRIC_DISTINCT_CAMPAIGN_RECORD_NAME       => $distinctCampaigns,
            Archiver::METRIC_DISTINCT_WEBSITE_RECORD_NAME        => $distinctWebsites,
            // TODO: distinct urls? don't think the data is available
        );

        $this->insertNumericRecords($numericRecords);
    }

    private function getKeywordByCampaign(Date $day)
    {
        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, $dimensions = ['ga:campaign', 'ga:keyword'], $this->getConversionAwareVisitMetrics());

        $keywordByCampaign = new DataTable();
        foreach ($table->getRows() as $row) {
            $campaign = $row->getMetadata('ga:campaign');
            if (empty($campaign)) {
                continue;
            }

            $keyword = $row->getMetadata('ga:keyword');
            if (empty($keyword)) {
                $keyword = self::NOT_SET_IN_GA_LABEL;
            }

            $this->campaignKeywords[$keyword] = true;

            $topLevelRow = $this->addRowToTable($keywordByCampaign, $row, $campaign);
            $this->addRowToSubtable($topLevelRow, $row, $keyword);

            // add to referrer type table
            $this->addRowToTable($this->referrerTypeRecord, $row, Common::REFERRER_TYPE_CAMPAIGN);
        }
        return $keywordByCampaign;
    }

    private function getUrlByWebsite(Date $day)
    {
        $social = Social::getInstance();

        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, $dimensions = ['ga:fullReferrer', 'ga:medium'], $this->getConversionAwareVisitMetrics());

        $urlByWebsite = new DataTable();
        $urlBySocialNetwork = new DataTable();
        foreach ($table->getRows() as $row) {
            $referrerUrl = $row->getMetadata('ga:fullReferrer');
            if ($referrerUrl == '(direct)') {
                continue;
            }

            $medium = $row->getMetadata('ga:medium');
            if ($medium != 'referral') {
                continue;
            }

            // URLs don't have protocols in GA
            $referrerUrl = 'http://' . $referrerUrl;

            // URLs can have extra information appended towards the end (like, ' iphone') in old data
            $parts = explode(' ', $referrerUrl);
            if (count($parts) == 2) {
                $referrerUrl = $parts[0];
            }

            // skip if this isn't a URL
            if (!filter_var($referrerUrl, FILTER_VALIDATE_URL)) {
                $this->getLogger()->warning("Non referrer URL encountered: $referrerUrl");
                continue;
            }

            $socialNetwork = $social->getSocialNetworkFromDomain($referrerUrl);
            if (!empty($socialNetwork)
                && $socialNetwork !== Piwik::translate('General_Unknown')
            ) {
                $topLevelRow = $this->addRowToTable($urlBySocialNetwork, $row, $socialNetwork);
                $this->addRowToSubtable($topLevelRow, $row, $referrerUrl);

                $this->addRowToTable($this->referrerTypeRecord, $row, Common::REFERRER_TYPE_SOCIAL_NETWORK);
            } else {
                $parsedUrl = @parse_url($referrerUrl);
                $host = isset($parsedUrl['host']) ? $parsedUrl['host'] : self::NOT_SET_IN_GA_LABEL;

                $topLevelRow = $this->addRowToTable($urlByWebsite, $row, $host);
                $this->addRowToSubtable($topLevelRow, $row, $referrerUrl);

                $this->addRowToTable($this->referrerTypeRecord, $row, Common::REFERRER_TYPE_WEBSITE);
            }
        }

        Common::destroy($table);

        return [$urlByWebsite, $urlBySocialNetwork];
    }

    private function getKeywordsAndSearchEngineRecords(Date $day)
    {
        $keywordBySearchEngine = new DataTable();
        $searchEngineByKeyword = new DataTable();

        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, $dimensions = ['ga:source', 'ga:medium', 'ga:keyword', 'ga:campaign'], $this->getConversionAwareVisitMetrics());

        foreach ($table->getRows() as $row) {
            $source = $row->getMetadata('ga:source');
            $medium = $row->getMetadata('ga:medium');
            $keyword = $row->getMetadata('ga:keyword');
            $campaign = $row->getMetadata('ga:campaign');

            if ($medium == 'referral') {
                $searchEngineName = $this->searchEngineMapper->mapReferralMediumToSearchEngine($medium);
                if (empty($searchEngineName)) {
                    continue;
                }
            } else if ($medium == 'organic') { // not a search engine referrer
                $searchEngineName = $this->searchEngineMapper->mapSourceToSearchEngine($source);
            } else {
                continue;
            }

            if (!empty($campaign)) {
                continue;
            }

            if (empty($searchEngineName)) {
                $searchEngineName = 'Unknown';
            }

            if (empty($keyword)) {
                $keyword = self::NOT_SET_IN_GA_LABEL;
            }

            // add to keyword by search engine record
            $topLevelRow = $this->addRowToTable($keywordBySearchEngine, $row, $keyword);
            $this->addRowToSubtable($topLevelRow, $row, $searchEngineName);

            // add to search engine by keyword record
            $topLevelRow = $this->addRowToTable($searchEngineByKeyword, $row, $searchEngineName);
            $this->addRowToSubtable($topLevelRow, $row, $keyword);

            $this->addRowToTable($this->referrerTypeRecord, $row, Common::REFERRER_TYPE_SEARCH_ENGINE);
        }

        Common::destroy($table);

        return [$keywordBySearchEngine, $searchEngineByKeyword];
    }

    private function queryNumberOfDirectEntries(Date $day)
    {
        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, $dimensions = ['ga:source'], $this->getConversionAwareVisitMetrics());

        foreach ($table->getRows() as $row) {
            $source = $row->getMetadata('ga:source');

            // invalid rows for direct entries and search engines
            if ($source == '(direct)') {
                $this->addRowToTable($this->referrerTypeRecord, $row, Common::REFERRER_TYPE_DIRECT_ENTRY);
                return;
            }
        }
    }
}