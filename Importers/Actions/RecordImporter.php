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
use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Date;
use Piwik\Metrics;
use Piwik\Plugins\Actions\Actions\ActionSiteSearch;
use Piwik\Plugins\Actions\Archiver;
use Piwik\Plugins\Actions\ArchivingHelper;
use Piwik\Plugins\MobileAppMeasurable\Type;
use Piwik\Site;
use Piwik\Tracker\Action;

class RecordImporter extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporter
{
    const PLUGIN_NAME = 'Actions';

    /**
     * @var DataTable[]
     */
    private $dataTables;

    /**
     * @var Row
     */
    private $pageTitleRowsByPageTitle;

    /**
     * @var Row[]
     */
    private $pageUrlsByPagePath;
    private $siteSearchUrls;

    public function supportsSite()
    {
        return Site::getTypeFor($this->getIdSite()) != Type::ID;
    }

    public function importRecords(Date $day)
    {
        ArchivingHelper::reloadConfig();

        $this->dataTables = [
            Action::TYPE_PAGE_URL => $this->makeDataTable(ArchivingHelper::$maximumRowsInDataTableLevelZero),
            Action::TYPE_PAGE_TITLE => $this->makeDataTable(ArchivingHelper::$maximumRowsInDataTableLevelZero),
            Action::TYPE_SITE_SEARCH => $this->makeDataTable(ArchivingHelper::$maximumRowsInDataTableSiteSearch),
        ];

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

        ArchivingHelper::setFolderPathMetadata($this->dataTables[Action::TYPE_PAGE_TITLE], $isUrl = false);
        ArchivingHelper::setFolderPathMetadata($this->dataTables[Action::TYPE_PAGE_URL], $isUrl = true, $folderPrefix = '');

        $this->insertDataTable(Action::TYPE_PAGE_TITLE, Archiver::PAGE_TITLES_RECORD_NAME);
        $this->insertDataTable(Action::TYPE_PAGE_URL, Archiver::PAGE_URLS_RECORD_NAME);
        $this->insertDataTable(Action::TYPE_SITE_SEARCH, Archiver::SITE_SEARCH_RECORD_NAME);

        $this->insertPageUrlNumericRecords($this->dataTables[Action::TYPE_PAGE_URL]);
        $this->insertSiteSearchNumericRecords($this->dataTables[Action::TYPE_SITE_SEARCH]);

        unset($this->pageTitleRowsByPageTitle);
        unset($this->pageUrlsByPagePath);
        unset($this->siteSearchUrls);

        foreach ($this->dataTables as &$table) {
            Common::destroy($table);
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
        $records = array(
            Archiver::METRIC_PAGEVIEWS_RECORD_NAME      => array_sum($pageUrls->getColumn(Metrics::INDEX_PAGE_NB_HITS)),
            Archiver::METRIC_UNIQ_PAGEVIEWS_RECORD_NAME => array_sum($pageUrls->getColumn(Metrics::INDEX_NB_VISITS)),
            Archiver::METRIC_SUM_TIME_RECORD_NAME       => array_sum($pageUrls->getColumn(Metrics::INDEX_PAGE_SUM_TIME_GENERATION)),
            Archiver::METRIC_HITS_TIMED_RECORD_NAME     => array_sum($pageUrls->getColumn(Metrics::INDEX_PAGE_NB_HITS_WITH_TIME_GENERATION))
        );
        $this->insertNumericRecords($records);
    }

    private function insertSiteSearchNumericRecords(DataTable $siteSearch)
    {
        $this->insertNumericRecords([
            Archiver::METRIC_SEARCHES_RECORD_NAME => array_sum($siteSearch->getColumn(Metrics::INDEX_PAGE_NB_HITS)),
            Archiver::METRIC_KEYWORDS_RECORD_NAME => $siteSearch->getRowsCount(),
        ]);
    }

    private function getSiteSearchs(Date $day)
    {
        $gaQuery = $this->getGaQuery();

        $metrics = array_merge([Metrics::INDEX_NB_VISITS, Metrics::INDEX_NB_UNIQ_VISITORS], $this->getPageMetrics());

        $table = $gaQuery->query($day, $dimensions = ['ga:searchKeyword'], $metrics, [
            'orderBys' => [
                ['field' => 'ga:searchUniques', 'order' => 'descending'],
                ['field' => 'ga:searchKeyword', 'order' => 'ascending']
            ],
            'mappings' => [
                Metrics::INDEX_NB_VISITS => 'ga:searchUniques',
                Metrics::INDEX_PAGE_NB_HITS => 'ga:searchResultViews',
            ],
        ]);

        foreach ($table->getRows() as $row) {
            $keyword = $row->getMetadata('ga:searchKeyword');

            $actionRow = ArchivingHelper::getActionRow($keyword, Action::TYPE_SITE_SEARCH, $urlPrefix = '', $this->dataTables);

            $row->deleteColumn('label');

            $actionRow->sumRow($row, $copyMetadata = false);
        }

        Common::destroy($table);
    }

    private function queryEntryPages(Date $day)
    {
        $entryPageMetrics = [
            Metrics::INDEX_PAGE_ENTRY_NB_UNIQ_VISITORS,
            Metrics::INDEX_PAGE_ENTRY_NB_VISITS,
            Metrics::INDEX_PAGE_ENTRY_NB_ACTIONS,
            Metrics::INDEX_PAGE_ENTRY_SUM_VISIT_LENGTH,
            Metrics::INDEX_PAGE_ENTRY_BOUNCE_COUNT,
        ];

        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, $dimensions = ['ga:landingPagePath'], $entryPageMetrics, [
            'orderBys' => [
                ['field' => 'ga:entrances', 'order' => 'descending'],
                ['field' => 'ga:landingPagePath', 'order' => 'ascending']
            ],
        ]);

        $mainUrlWithoutSlash = Site::getMainUrlFor($this->getIdSite());
        $mainUrlWithoutSlash = rtrim($mainUrlWithoutSlash, '/');

        foreach ($table->getRows() as $row) {
            $actionName = $mainUrlWithoutSlash . $row->getMetadata('ga:landingPagePath');
            $row->deleteColumn('label');

            if (isset($this->pageUrlsByPagePath[$actionName])) {
                if ($this->pageUrlsByPagePath[$actionName]->hasColumn(Metrics::INDEX_PAGE_ENTRY_NB_VISITS)
                    && $this->pageUrlsByPagePath[$actionName]->getColumn('label') != DataTable::LABEL_SUMMARY_ROW
                ) {
                    throw new \Exception("Unexpected error: encountered URL twice in result set: '$actionName'");
                }

                $this->pageUrlsByPagePath[$actionName]->sumRow($row, $copyMetadata = false);
            }
        }

        Common::destroy($table);

        // query page titles
        $table = $gaQuery->query($day, $dimensions = ['ga:pageTitle'], $entryPageMetrics, [
            'orderBys' => [
                ['field' => 'ga:entrances', 'order' => 'descending'],
                ['field' => 'ga:pageTitle', 'order' => 'ascending']
            ],
        ]);

        foreach ($table->getRows() as $row) {
            $pageTitle = $row->getMetadata('ga:pageTitle');
            $row->deleteColumn('label');

            if (isset($this->pageTitleRowsByPageTitle[$pageTitle])) {
                $existingRow = $this->pageTitleRowsByPageTitle[$pageTitle];
                if ($existingRow->hasColumn(Metrics::INDEX_PAGE_ENTRY_NB_VISITS)
                    && $existingRow->getColumn('label') != DataTable::LABEL_SUMMARY_ROW
                ) {
                    throw new \Exception("Unexpected error: encountered page title twice in result set: '$actionName'");
                }

                $existingRow->sumRow($row, $copyMetadata = false);
            }
        }

        Common::destroy($table);
    }

    private function queryExitPages(Date $day)
    {
        $exitPageMetrics = [
            Metrics::INDEX_PAGE_EXIT_NB_UNIQ_VISITORS,
            Metrics::INDEX_PAGE_EXIT_NB_VISITS,
        ];

        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, $dimensions = ['ga:exitPagePath'], $exitPageMetrics, [
            'orderBys' => [
                ['field' => 'ga:exits', 'order' => 'descending'],
                ['field' => 'ga:exitPagePath', 'order' => 'ascending']
            ],
        ]);

        $mainUrlWithoutSlash = Site::getMainUrlFor($this->getIdSite());
        $mainUrlWithoutSlash = rtrim($mainUrlWithoutSlash, '/');

        foreach ($table->getRows() as $row) {
            $actionName = $mainUrlWithoutSlash . $row->getMetadata('ga:exitPagePath');

            $row->deleteColumn('label');

            if (isset($this->pageUrlsByPagePath[$actionName])) {
                if ($this->pageUrlsByPagePath[$actionName]->hasColumn(Metrics::INDEX_PAGE_EXIT_NB_VISITS)
                    && $this->pageUrlsByPagePath[$actionName]->getColumn('label') != DataTable::LABEL_SUMMARY_ROW
                ) {
                    throw new \Exception("Unexpected error: encountered URL twice in result set: '$actionName'");
                }

                $this->pageUrlsByPagePath[$actionName]->sumRow($row, $copyMetadata = false);
            }
        }

        Common::destroy($table);

        // query page titles
        $table = $gaQuery->query($day, $dimensions = ['ga:pageTitle'], $exitPageMetrics, [
            'orderBys' => [
                ['field' => 'ga:exits', 'order' => 'descending'],
                ['field' => 'ga:pageTitle', 'order' => 'ascending']
            ],
        ]);

        foreach ($table->getRows() as $row) {
            $pageTitle = $row->getMetadata('ga:pageTitle');
            $row->deleteColumn('label');

            if (empty($this->pageTitleRowsByPageTitle[$pageTitle])) {
                continue;
            }

            $existingRow = $this->pageTitleRowsByPageTitle[$pageTitle];
            if ($existingRow->hasColumn(Metrics::INDEX_PAGE_EXIT_NB_VISITS)
                && $existingRow->getColumn('label') != DataTable::LABEL_SUMMARY_ROW
            ) {
                throw new \Exception("Unexpected error: encountered page title twice in result set: '$actionName'");
            }

            $existingRow->sumRow($row, $copyMetadata = false);
        }

        Common::destroy($table);
    }

    private function getPageTitlesRecord(Date $day)
    {
        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, $dimensions = ['ga:pageTitle', 'ga:pagePath'], $this->getPageMetrics(), [
            'orderBys' => [
                ['field' => 'ga:uniquePageviews', 'order' => 'descending'],
                ['field' => 'ga:pageTitle', 'order' => 'ascending'],
            ],
        ]);

        // pageTitle + pagePath combination is not supported for this date
        if ($table->getRowsCount() == 0) {
            $table = $gaQuery->query($day, $dimensions = ['ga:pageTitle'], $this->getPageMetrics(), [
                'orderBys' => [
                    ['field' => 'ga:uniquePageviews', 'order' => 'descending'],
                    ['field' => 'ga:pageTitle', 'order' => 'ascending'],
                ],
            ]);
        }

        foreach ($table->getRows() as $row) {
            $pagePath = $row->getMetadata('ga:pagePath');
            if (!empty($pagePath) && !empty($this->siteSearchUrls[$pagePath])) { // skip site search pages
                continue;
            }

            $actionName = $row->getMetadata('ga:pageTitle');
            $actionRow = ArchivingHelper::getActionRow($actionName, Action::TYPE_PAGE_TITLE, $urlPrefix = null, $this->dataTables);

            $row->deleteColumn('label');
            $actionRow->sumRow($row, $copyMetadata = false);

            $this->pageTitleRowsByPageTitle[$actionName] = $actionRow;
        }

        Common::destroy($table);

        // query for visits/unique visitors (GA seems to provide inaccurate metrics sometimes if we combine this w/ the above metrics)
        $metrics = [Metrics::INDEX_NB_VISITS, Metrics::INDEX_NB_UNIQ_VISITORS];
        $table = $gaQuery->query($day, $dimensions = ['ga:pageTitle'], $metrics, [
            'orderBys' => [
                ['field' => 'ga:sessions', 'order' => 'descending'],
                ['field' => 'ga:pageTitle', 'order' => 'ascending'],
            ],
            'mappings' => [
                Metrics::INDEX_NB_VISITS => 'ga:uniquePageviews',
            ],
        ]);

        foreach ($table->getRows() as $row) {
            $row->deleteColumn('label');

            $pageTitle = $row->getMetadata('ga:pageTitle');

            if (!empty($this->pageTitleRowsByPageTitle[$pageTitle])) {
                $recordRow = $this->pageTitleRowsByPageTitle[$pageTitle];
                $recordRow->sumRow($row, $copyMetadata = false);
            }
        }

        Common::destroy($table);
    }

    private function getPageUrlsRecord(Date $day)
    {
        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, $dimensions = ['ga:pagePath'], $this->getPageMetrics(), [
            'orderBys' => [
                ['field' => 'ga:uniquePageviews', 'order' => 'descending'],
                ['field' => 'ga:pagePath', 'order' => 'ascending']
            ],
        ]);

        $siteDetails = Request::processRequest('SitesManager.getSiteFromId', [
            'idSite' => $this->getIdSite(),
        ], $defaultRequest = []);
        $siteDetails['sitesearch_keyword_parameters'] = explode(',', $siteDetails['sitesearch_keyword_parameters']);
        $siteDetails['sitesearch_category_parameters'] = explode(',', $siteDetails['sitesearch_category_parameters']);

        $mainUrlWithoutSlash = Site::getMainUrlFor($this->getIdSite());
        $mainUrlWithoutSlash = rtrim($mainUrlWithoutSlash, '/');

        foreach ($table->getRows() as $row) {
            $actionName = $row->getMetadata('ga:pagePath') ?: '/';

            // sometimes the metrics returned can be 0, no need to add the row in that case
            if (empty($row->getColumn(Metrics::INDEX_PAGE_NB_HITS))) {
                continue;
            }

            $wholeUrl = $mainUrlWithoutSlash . $actionName;

            // google removes the search keyword from the URL, but just in case, try to detect it and exclude it from the appropriate reports
            $parsedUrl = parse_url($wholeUrl);
            $isSiteSearch = ActionSiteSearch::detectSiteSearchFromUrl($siteDetails, $parsedUrl);
            if ($isSiteSearch) {
                $this->siteSearchUrls[$actionName] = true;
                continue;
            }

            $actionRow = ArchivingHelper::getActionRow('dummyhost.com' . $actionName, Action::TYPE_PAGE_URL, '', $this->dataTables);

            $row->deleteColumn('label');

            $actionRow->sumRow($row, $copyMetadata = false);
            if ($actionRow->getColumn('label') != DataTable::LABEL_SUMMARY_ROW) {
                $actionRow->setMetadata('url', $wholeUrl);
            }

            $this->pageUrlsByPagePath[$wholeUrl] = $actionRow;
        }

        Common::destroy($table);

        // query for visits/unique visitors (GA seems to provide inaccurate metrics sometimes if we combine this w/ the above metrics)
        $metrics = [Metrics::INDEX_NB_VISITS, Metrics::INDEX_NB_UNIQ_VISITORS];
        $table = $gaQuery->query($day, $dimensions = ['ga:pagePath'], $metrics, [
            'orderBys' => [
                ['field' => 'ga:uniquePageviews', 'order' => 'descending'],
                ['field' => 'ga:pageTitle', 'order' => 'ascending'],
            ],
            'mappings' => [
                Metrics::INDEX_NB_VISITS => 'ga:uniquePageviews',
            ],
        ]);

        foreach ($table->getRows() as $row) {
            $row->deleteColumn('label');

            $actionName = $row->getMetadata('ga:pagePath');
            $wholeUrl = $mainUrlWithoutSlash . $actionName;

            if (!empty($this->pageUrlsByPagePath[$wholeUrl])) {
                $recordRow = $this->pageUrlsByPagePath[$wholeUrl];
                $recordRow->sumRow($row, $copyMetadata = false);
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
        $this->insertRecord($recordName, $this->dataTables[$actionType], ArchivingHelper::$maximumRowsInDataTableLevelZero,
            ArchivingHelper::$maximumRowsInSubDataTable, ArchivingHelper::$columnToSortByBeforeTruncation);
    }
}
