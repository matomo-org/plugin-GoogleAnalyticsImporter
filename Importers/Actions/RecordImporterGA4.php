<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\Importers\Actions;

use Piwik\API\Request;
use Piwik\Common;
use Piwik\Config;
use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Date;
use Piwik\Metrics;
use Piwik\Plugins\Actions\Actions\ActionSiteSearch;
use Piwik\Plugins\Actions\Archiver;
use Piwik\Plugins\Actions\ArchivingHelper;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleAnalyticsGA4QueryService;
use Piwik\Plugins\MobileAppMeasurable\Type;
use Piwik\Site;
use Piwik\Tracker\Action;
use Piwik\Log\LoggerInterface;
class RecordImporterGA4 extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporterGA4
{
    const PLUGIN_NAME = 'Actions';
    const LABEL_DEFAULT_NAME = '__mtm_ga_default_name_placeholder__';
    /**
     * @var DataTable[]
     */
    private $dataTables;
    /**
     * @var Row[]
     */
    private $pageTitleRowsByPageTitle;
    /**
     * @var Row[]
     */
    private $pageUrlsByPagePath;
    private $siteSearchUrls;
    private $entryPageMetrics = [
        Metrics::INDEX_PAGE_ENTRY_NB_UNIQ_VISITORS,
        //        Metrics::INDEX_PAGE_ENTRY_NB_VISITS, Not available in GA4
        Metrics::INDEX_PAGE_ENTRY_NB_ACTIONS,
        Metrics::INDEX_PAGE_ENTRY_SUM_VISIT_LENGTH,
        Metrics::INDEX_PAGE_ENTRY_BOUNCE_COUNT,
    ];
    private $exitPageMetrics = [Metrics::INDEX_PAGE_EXIT_NB_UNIQ_VISITORS];
    private $isMobileApp;
    private $pageTitleDimension;
    //    private $uniquePageviewsMetric; Not available in GA4
    private $hitsMetric;
    private $pageTitleEntryDimensions;
    private $pageTitleExitDimensions;
    public function __construct(GoogleAnalyticsGA4QueryService $gaQuery, $idSite, LoggerInterface $logger)
    {
        parent::__construct($gaQuery, $idSite, $logger);
        $this->isMobileApp = Site::getTypeFor($this->getIdSite()) == Type::ID;
        $this->pageTitleDimension = $this->isMobileApp ? 'unifiedScreenName' : 'pageTitle';
        //        $this->uniquePageviewsMetric = $this->isMobileApp ? 'ga:uniqueScreenviews' : 'ga:uniquePageviews'; Not available in GA4
        $this->hitsMetric = 'screenPageViews';
        $this->pageTitleEntryDimensions = $this->isMobileApp ? ['unifiedScreenName'] : ['landingPage', 'pageTitle'];
        //        $this->pageTitleExitDimensions = $this->isMobileApp ? ['ga:exitScreenName'] : ['ga:exitPagePath', 'pageTitle']; Not available in GA4
    }
    public function importRecords(Date $day)
    {
        $originalDefaultName = Config::getInstance()->General['action_default_name'];
        Config::getInstance()->General['action_default_name'] = self::LABEL_DEFAULT_NAME;
        try {
            ArchivingHelper::reloadConfig();
            $this->dataTables = [Action::TYPE_PAGE_URL => $this->makeDataTable(ArchivingHelper::$maximumRowsInDataTableLevelZero), Action::TYPE_PAGE_TITLE => $this->makeDataTable(ArchivingHelper::$maximumRowsInDataTableLevelZero), Action::TYPE_SITE_SEARCH => $this->makeDataTable(ArchivingHelper::$maximumRowsInDataTableSiteSearch)];
            $this->pageTitleRowsByPageTitle = [];
            $this->pageUrlsByPagePath = [];
            $this->siteSearchUrls = [];
            // query for records
            $this->getPageUrlsRecord($day);
            $this->getPageTitlesRecord($day);
            $this->queryEntryPages($day);
            $this->queryExitPages($day);
            $this->getSiteSearchs($day);
            $this->queryPagesFollowingSiteSearch($day);
            //            $this->querySiteSearchCategories($day); Not available in GA4
            ArchivingHelper::setFolderPathMetadata($this->dataTables[Action::TYPE_PAGE_TITLE], $isUrl = \false);
            ArchivingHelper::setFolderPathMetadata($this->dataTables[Action::TYPE_PAGE_URL], $isUrl = \true, $folderPrefix = '');
            $this->replaceDefaultActionName($originalDefaultName);
            $this->insertDataTable(Action::TYPE_PAGE_TITLE, Archiver::PAGE_TITLES_RECORD_NAME);
            $this->insertDataTable(Action::TYPE_PAGE_URL, Archiver::PAGE_URLS_RECORD_NAME);
            $this->insertDataTable(Action::TYPE_SITE_SEARCH, Archiver::SITE_SEARCH_RECORD_NAME);
            $pageReportToUse = $this->isMobileApp ? $this->dataTables[Action::TYPE_PAGE_TITLE] : $this->dataTables[Action::TYPE_PAGE_URL];
            $this->insertPageUrlNumericRecords($pageReportToUse);
            $this->insertSiteSearchNumericRecords($this->dataTables[Action::TYPE_SITE_SEARCH]);
            unset($this->pageTitleRowsByPageTitle);
            unset($this->pageUrlsByPagePath);
            unset($this->siteSearchUrls);
            foreach ($this->dataTables as &$table) {
                Common::destroy($table);
            }
            unset($this->dataTables);
        } finally {
            Config::getInstance()->General['action_default_name'] = $originalDefaultName;
            ArchivingHelper::reloadConfig();
        }
        // TODO: bandwidth metrics
        // TODO: downloads, outlinks (requires segment on event and event configuration)
    }
    private function queryPagesFollowingSiteSearch(Date $day)
    {
        // TODO: there is a ga:searchAfterDestinationPage dimension, but I am not sure how to get number of hits that were after site search, and not ALL hits
    }
    private function insertPageUrlNumericRecords(DataTable $pageUrls)
    {
        $records = array(Archiver::METRIC_PAGEVIEWS_RECORD_NAME => array_sum($pageUrls->getColumn(Metrics::INDEX_PAGE_NB_HITS)), Archiver::METRIC_UNIQ_PAGEVIEWS_RECORD_NAME => array_sum($pageUrls->getColumn(Metrics::INDEX_NB_VISITS)));
        $this->insertNumericRecords($records);
    }
    private function insertSiteSearchNumericRecords(DataTable $siteSearch)
    {
        $this->insertNumericRecords([Archiver::METRIC_SEARCHES_RECORD_NAME => array_sum($siteSearch->getColumn(Metrics::INDEX_PAGE_NB_HITS)), Archiver::METRIC_KEYWORDS_RECORD_NAME => $siteSearch->getRowsCount()]);
    }
    private function getSiteSearchs(Date $day)
    {
        if ($this->isMobileApp) {
            $this->getLogger()->debug("Skipping import of site search for mobile app property.");
            return;
        }
        $gaQuery = $this->getGaClient();
        $metrics = array_merge([Metrics::INDEX_NB_VISITS, Metrics::INDEX_NB_UNIQ_VISITORS], $this->getPageMetrics());
        $table = $gaQuery->query($day, $dimensions = ['searchTerm'], $metrics, ['orderBys' => [
            //                ['field' => 'ga:searchUniques', 'order' => 'descending'], Not available in GA4
            ['field' => 'searchTerm', 'order' => 'ascending'],
        ], 'mappings' => []]);
        foreach ($table->getRows() as $row) {
            $keyword = $row->getMetadata('searchTerm');
            $actionRow = ArchivingHelper::getActionRow($keyword, Action::TYPE_SITE_SEARCH, $urlPrefix = '', $this->dataTables);
            $row->deleteColumn('label');
            $actionRow->sumRow($row, $copyMetadata = \false);
        }
        Common::destroy($table);
    }
    private function queryEntryPages(Date $day)
    {
        $this->queryEntryPagesForUrls($day);
        $this->queryEntryPagesForTitles($day);
    }
    private function queryExitPages(Date $day)
    {
        //Not available in GA4
        //        $this->queryExitPagesForUrls($day);
        //        $this->queryExitPagesForTitles($day);
    }
    private function getPageTitlesRecord(Date $day)
    {
        $gaQuery = $this->getGaClient();
        if ($this->isMobileApp) {
            $table = $gaQuery->query($day, $dimensions = [$this->pageTitleDimension], $this->getPageMetrics(), ['orderBys' => [], 'mappings' => [Metrics::INDEX_PAGE_NB_HITS => $this->hitsMetric]]);
        } else {
            $table = $gaQuery->query($day, $dimensions = [$this->pageTitleDimension, 'pagePath'], $this->getPageMetrics(), ['orderBys' => [['field' => 'screenPageViews', 'order' => 'descending'], ['field' => $this->pageTitleDimension, 'order' => 'ascending']]]);
            // pageTitle + pagePath combination is not supported for this date
            if ($table->getRowsCount() == 0) {
                $table = $gaQuery->query($day, $dimensions = [$this->pageTitleDimension], $this->getPageMetrics(), ['orderBys' => [['field' => 'screenPageViews', 'order' => 'descending'], ['field' => $this->pageTitleDimension, 'order' => 'ascending']]]);
            }
        }
        foreach ($table->getRows() as $row) {
            $pagePath = $row->getMetadata('pagePath');
            if (!empty($pagePath) && !empty($this->siteSearchUrls[$pagePath])) {
                // skip site search pages
                continue;
            }
            $actionName = $row->getMetadata($this->pageTitleDimension);
            $actionRow = ArchivingHelper::getActionRow($actionName, Action::TYPE_PAGE_TITLE, $urlPrefix = null, $this->dataTables);
            $row->deleteColumn('label');
            $actionRow->sumRow($row, $copyMetadata = \false);
            $this->pageTitleRowsByPageTitle[$actionName] = $actionRow;
        }
        Common::destroy($table);
        // query for visits/unique visitors w/o page path
        $metrics = [Metrics::INDEX_NB_VISITS, Metrics::INDEX_NB_UNIQ_VISITORS];
        $table = $gaQuery->query($day, $dimensions = [$this->pageTitleDimension], $metrics, ['orderBys' => [
            //                ['field' => $this->uniquePageviewsMetric, 'order' => 'descending'], Not available in GA4
            ['field' => $this->pageTitleDimension, 'order' => 'ascending'],
        ], 'mappings' => []]);
        foreach ($table->getRows() as $row) {
            $row->deleteColumn('label');
            $pageTitle = $row->getMetadata($this->pageTitleDimension);
            if (!empty($this->pageTitleRowsByPageTitle[$pageTitle])) {
                $recordRow = $this->pageTitleRowsByPageTitle[$pageTitle];
                $recordRow->sumRow($row, $copyMetadata = \false);
            }
        }
        Common::destroy($table);
    }
    private function getPageUrlsRecord(Date $day)
    {
        if ($this->isMobileApp) {
            $this->getLogger()->debug("Skipping import of page urls for mobile app property.");
            return;
        }
        $gaQuery = $this->getGaClient();
        $table = $gaQuery->query($day, $dimensions = ['pagePath'], $this->getPageMetrics(), ['orderBys' => [['field' => 'screenPageViews', 'order' => 'descending'], ['field' => 'pagePath', 'order' => 'ascending']]]);
        $siteDetails = Request::processRequest('SitesManager.getSiteFromId', ['idSite' => $this->getIdSite()], $defaultRequest = []);
        $siteDetails['sitesearch_keyword_parameters'] = explode(',', $siteDetails['sitesearch_keyword_parameters']);
        $siteDetails['sitesearch_category_parameters'] = explode(',', $siteDetails['sitesearch_category_parameters']);
        $mainUrlWithoutSlash = Site::getMainUrlFor($this->getIdSite());
        $mainUrlWithoutSlash = rtrim($mainUrlWithoutSlash, '/');
        foreach ($table->getRows() as $row) {
            $actionName = $row->getMetadata('pagePath') ?: '/';
            // sometimes the metrics returned can be 0, no need to add the row in that case
            if (empty($row->getColumn(Metrics::INDEX_PAGE_NB_HITS))) {
                continue;
            }
            $wholeUrl = $mainUrlWithoutSlash . $actionName;
            // google removes the search keyword from the URL, but just in case, try to detect it and exclude it from the appropriate reports
            $parsedUrl = parse_url($wholeUrl);
            $isSiteSearch = ActionSiteSearch::detectSiteSearchFromUrl($siteDetails, $parsedUrl);
            if ($isSiteSearch) {
                $this->siteSearchUrls[$actionName] = \true;
                continue;
            }
            $actionRow = ArchivingHelper::getActionRow('dummyhost.com' . $actionName, Action::TYPE_PAGE_URL, '', $this->dataTables);
            $row->deleteColumn('label');
            $actionRow->sumRow($row, $copyMetadata = \false);
            if ($actionRow->getColumn('label') != DataTable::LABEL_SUMMARY_ROW) {
                $actionRow->setMetadata('url', $wholeUrl);
            }
            $this->pageUrlsByPagePath[$wholeUrl] = $actionRow;
        }
        Common::destroy($table);
        // query for visits/unique visitors (GA seems to provide inaccurate metrics sometimes if we combine this w/ the above metrics)
        $metrics = [Metrics::INDEX_NB_VISITS, Metrics::INDEX_NB_UNIQ_VISITORS];
        $table = $gaQuery->query($day, $dimensions = ['pagePath'], $metrics, ['orderBys' => [
            //                ['field' => 'ga:uniquePageviews', 'order' => 'descending'],  Not available in GA4
            ['field' => 'pagePath', 'order' => 'ascending'],
        ], 'mappings' => []]);
        foreach ($table->getRows() as $row) {
            $row->deleteColumn('label');
            $actionName = $row->getMetadata('pagePath');
            $wholeUrl = $mainUrlWithoutSlash . $actionName;
            if (!empty($this->pageUrlsByPagePath[$wholeUrl])) {
                $recordRow = $this->pageUrlsByPagePath[$wholeUrl];
                $recordRow->sumRow($row, $copyMetadata = \false);
            }
        }
        Common::destroy($table);
    }
    private function makeDataTable($maxAllowedRows)
    {
        $table = new DataTable();
        $table->setMaximumAllowedRows($maxAllowedRows);
        return $table;
    }
    private function insertDataTable($actionType, $recordName)
    {
        ArchivingHelper::deleteInvalidSummedColumnsFromDataTable($this->dataTables[$actionType]);
        $this->insertRecord($recordName, $this->dataTables[$actionType], ArchivingHelper::$maximumRowsInDataTableLevelZero, ArchivingHelper::$maximumRowsInSubDataTable, ArchivingHelper::$columnToSortByBeforeTruncation);
    }
    private function queryEntryPagesForUrls(Date $day)
    {
        if ($this->isMobileApp) {
            $this->getLogger()->debug("Skipping import of entry page urls for mobile app property.");
            return;
        }
        $gaQuery = $this->getGaClient();
        $table = $gaQuery->query($day, $dimensions = ['landingPage'], $this->entryPageMetrics, ['orderBys' => [
            //                ['field' => 'ga:entrances', 'order' => 'descending'], Not available in GA4
            ['field' => 'landingPage', 'order' => 'ascending'],
        ]]);
        $mainUrlWithoutSlash = Site::getMainUrlFor($this->getIdSite());
        $mainUrlWithoutSlash = rtrim($mainUrlWithoutSlash, '/');
        foreach ($table->getRows() as $row) {
            $actionName = $mainUrlWithoutSlash . $row->getMetadata('landingPage');
            $row->deleteColumn('label');
            if (isset($this->pageUrlsByPagePath[$actionName])) {
                if ($this->pageUrlsByPagePath[$actionName]->hasColumn(Metrics::INDEX_PAGE_ENTRY_NB_VISITS) && $this->pageUrlsByPagePath[$actionName]->getColumn('label') != DataTable::LABEL_SUMMARY_ROW) {
                    $this->getLogger()->warning("Unexpected error: encountered URL twice in result set: '{$actionName}'");
                    continue;
                }
                $this->pageUrlsByPagePath[$actionName]->sumRow($row, $copyMetadata = \false);
            }
        }
        Common::destroy($table);
    }
    private function queryEntryPagesForTitles(Date $day)
    {
        $entryPageTitleMetrics = $this->entryPageMetrics;
        if (!$this->isMobileApp) {
            // remove unique visitors metrics when querying for entry pageTitles, since there is no landingPageTitle dimension.
            // we have to use landingPagePath + pageTitle, but there can be more than one URL w/ the same page title, and
            // we can't aggregate unique visitors.
            $entryPageTitleMetrics = array_diff($entryPageTitleMetrics, [Metrics::INDEX_PAGE_ENTRY_NB_UNIQ_VISITORS]);
        }
        $pageTitleDimension = end($this->pageTitleEntryDimensions);
        $gaQuery = $this->getGaClient();
        $table = $gaQuery->query($day, $this->pageTitleEntryDimensions, $entryPageTitleMetrics, ['orderBys' => [
            //                ['field' => 'ga:entrances', 'order' => 'descending'], Not available in GA4
            ['field' => $pageTitleDimension, 'order' => 'ascending'],
        ]]);
        foreach ($table->getRows() as $row) {
            $pageTitle = $row->getMetadata($pageTitleDimension);
            $row->deleteColumn('label');
            if (isset($this->pageTitleRowsByPageTitle[$pageTitle])) {
                $existingRow = $this->pageTitleRowsByPageTitle[$pageTitle];
                if ($existingRow->hasColumn(Metrics::INDEX_PAGE_ENTRY_NB_UNIQ_VISITORS) && $existingRow->getColumn('label') != DataTable::LABEL_SUMMARY_ROW) {
                    $this->getLogger()->warning("Unexpected error: encountered page title twice in result set (when including unique visitors): '{$pageTitle}'");
                    continue;
                }
                $existingRow->sumRow($row, $copyMetadata = \false);
            }
        }
        Common::destroy($table);
    }
    private function queryExitPagesForUrls(Date $day)
    {
        if ($this->isMobileApp) {
            $this->getLogger()->debug("Skipping import of exit page urls for mobile app property.");
            return;
        }
        $gaQuery = $this->getGaClient();
        $table = $gaQuery->query($day, $dimensions = ['ga:exitPagePath'], $this->exitPageMetrics, ['orderBys' => [['field' => 'ga:exits', 'order' => 'descending'], ['field' => 'ga:exitPagePath', 'order' => 'ascending']]]);
        $mainUrlWithoutSlash = Site::getMainUrlFor($this->getIdSite());
        $mainUrlWithoutSlash = rtrim($mainUrlWithoutSlash, '/');
        foreach ($table->getRows() as $row) {
            $actionName = $mainUrlWithoutSlash . $row->getMetadata('ga:exitPagePath');
            $row->deleteColumn('label');
            if (isset($this->pageUrlsByPagePath[$actionName])) {
                if ($this->pageUrlsByPagePath[$actionName]->hasColumn(Metrics::INDEX_PAGE_EXIT_NB_VISITS) && $this->pageUrlsByPagePath[$actionName]->getColumn('label') != DataTable::LABEL_SUMMARY_ROW) {
                    $this->getLogger()->warning("Unexpected error: encountered URL twice in result set: '{$actionName}'");
                    continue;
                }
                $this->pageUrlsByPagePath[$actionName]->sumRow($row, $copyMetadata = \false);
            }
        }
        Common::destroy($table);
    }
    private function queryExitPagesForTitles(Date $day)
    {
        $exitPageTitleMetrics = $this->exitPageMetrics;
        if (!$this->isMobileApp) {
            // remove unique visitors metrics when querying for exit pageTitles, since there is no exitPageTitle dimension.
            // we have to use exitPagePath + pageTitle, but there can be more than one URL w/ the same page title, and
            // we can't aggregate unique visitors.
            $exitPageTitleMetrics = array_diff($exitPageTitleMetrics, [Metrics::INDEX_PAGE_EXIT_NB_UNIQ_VISITORS]);
        }
        $pageTitleDimension = end($this->pageTitleExitDimensions);
        // query page titles
        $gaQuery = $this->getGaClient();
        $table = $gaQuery->query($day, $this->pageTitleExitDimensions, $exitPageTitleMetrics, ['orderBys' => [['field' => 'ga:exits', 'order' => 'descending'], ['field' => $pageTitleDimension, 'order' => 'ascending']]]);
        foreach ($table->getRows() as $row) {
            $pageTitle = $row->getMetadata($pageTitleDimension);
            $row->deleteColumn('label');
            if (empty($this->pageTitleRowsByPageTitle[$pageTitle])) {
                // sanity check
                continue;
            }
            $existingRow = $this->pageTitleRowsByPageTitle[$pageTitle];
            if ($existingRow->hasColumn(Metrics::INDEX_PAGE_EXIT_NB_UNIQ_VISITORS) && $existingRow->getColumn('label') != DataTable::LABEL_SUMMARY_ROW) {
                $this->getLogger()->warning("Unexpected error: encountered page title twice in result set: '{$pageTitle}'");
                continue;
            }
            $existingRow->sumRow($row, $copyMetadata = \false);
        }
        Common::destroy($table);
    }
    private function replaceDefaultActionName($originalDefaultName)
    {
        foreach ($this->dataTables as $type => $table) {
            $this->replaceDefaultActionNameInTable($table, $originalDefaultName);
        }
    }
    private function replaceDefaultActionNameInTable(DataTable $table, $originalDefaultName)
    {
        foreach ($table->getRows() as $row) {
            $label = $row->getColumn('label');
            $label = str_replace(self::LABEL_DEFAULT_NAME, $originalDefaultName, $label);
            $row->setColumn('label', $label);
            $subtable = $row->getSubtable();
            if ($subtable) {
                $this->replaceDefaultActionNameInTable($subtable, $originalDefaultName);
            }
        }
    }
    private function querySiteSearchCategories(Date $day)
    {
        $record = new DataTable();
        $gaQuery = $this->getGaClient();
        $table = $gaQuery->query($day, $dimensions = ['ga:searchCategory'], array_merge($this->getConversionAwareVisitMetrics(), $this->getActionMetrics()), ['mappings' => [Metrics::INDEX_NB_VISITS => 'ga:searchUniques', Metrics::INDEX_NB_ACTIONS => 'ga:searchResultViews']]);
        foreach ($table->getRows() as $row) {
            $searchCategory = $row->getMetadata('ga:searchCategory');
            if (empty($searchCategory)) {
                $searchCategory = self::NOT_SET_IN_GA_LABEL;
            }
            $this->addRowToTable($record, $row, $searchCategory);
        }
        Common::destroy($table);
        $this->insertRecord(Archiver::SITE_SEARCH_CATEGORY_RECORD_NAME, $record);
    }
}
