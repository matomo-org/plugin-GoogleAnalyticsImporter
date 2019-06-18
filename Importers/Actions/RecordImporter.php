<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\Importers\Actions;

use Piwik\Common;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Metrics;
use Piwik\Period\Day;
use Piwik\Plugins\Actions\Archiver;
use Piwik\Plugins\Actions\ArchivingHelper;
use Piwik\Site;
use Piwik\Tracker\Action;

// TODO: numeric metrics
// TODO: folder path metadata
// TODO: sum_time_spent in actions reports seems off. it's 0 in a lot of cases...
// TODO: site search is untested since we don't have the data
class RecordImporter extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporter
{
    const PLUGIN_NAME = 'Actions';

    private $dataTables;

    private $pageTitlesByPagePath;
    private $pageUrlsByPagePath;

    public function queryGoogleAnalyticsApi(Date $day)
    {
        ArchivingHelper::reloadConfig();

        $this->dataTables = [
            Action::TYPE_PAGE_URL => $this->makeDataTable(ArchivingHelper::$maximumRowsInDataTableLevelZero),
            Action::TYPE_PAGE_TITLE => $this->makeDataTable(ArchivingHelper::$maximumRowsInDataTableLevelZero),
            Action::TYPE_SITE_SEARCH => $this->makeDataTable(ArchivingHelper::$maximumRowsInDataTableSiteSearch),
        ];

        $this->pageTitlesByPagePath = [];
        $this->pageUrlsByPagePath = [];

        // query for records
        $this->getPageTitlesRecord($day);
        $this->getPageUrlsRecord($day);
        $this->queryEntryPages($day);
        $this->queryExitPages($day);
        $this->getSiteSearchs($day);

        ArchivingHelper::setFolderPathMetadata($this->dataTables[Action::TYPE_PAGE_TITLE], $isUrl = false);
        ArchivingHelper::setFolderPathMetadata($this->dataTables[Action::TYPE_PAGE_URL], $isUrl = true, $folderPrefix = '');

        $this->insertDataTable(Action::TYPE_PAGE_TITLE, Archiver::PAGE_TITLES_RECORD_NAME);
        $this->insertDataTable(Action::TYPE_PAGE_URL, Archiver::PAGE_URLS_RECORD_NAME);

        unset($this->pageTitlesByPagePath);
        unset($this->pageUrlsByPagePath);

        foreach ($this->dataTables as &$table) {
            Common::destroy($table);
        }

        // TODO: bandwidth metrics
        // TODO: downloads, outlinks (requires segment on event and event configuration)
        // TODO: categories must go in custom variable record
    }

    private function getSiteSearchs(Date $day)
    {
        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, $dimensions = ['ga:searchKeyword'], $this->getPageMetrics(), [
            'orderBys' => [
                ['field' => 'ga:sessions', 'order' => 'descending'],
                ['field' => 'ga:searchKeyword', 'order' => 'ascending']
            ],
        ]);

        foreach ($table->getRows() as $row) {
            $keyword = $row->getMetadata('ga:searchKeyword');

            $actionRow = ArchivingHelper::getActionRow($keyword, Action::TYPE_SITE_SEARCH, $urlPrefix = '', $this->dataTables);

            $row->deleteColumn('label');

            $columns = $row->getColumns();
            foreach ($columns as $name => $value) {
                $actionRow->setColumn($name, $value);
            }
        }

        Common::destroy($table);
    }

    // TODO: should we order by hits instead? check actions archiver
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
        $table = $gaQuery->query($day, $dimensions = ['ga:hostname', 'ga:landingPagePath'], $entryPageMetrics, [
            'orderBys' => [
                ['field' => 'ga:sessions', 'order' => 'descending'],
                ['field' => 'ga:landingPagePath', 'order' => 'ascending']
            ],
        ]);

        foreach ($table->getRows() as $row) {
            $hostname = $row->getMetadata('ga:hostname');
            $actionName = 'http://' . $hostname . $row->getMetadata('ga:landingPagePath');

            $row->deleteColumn('label');

            if (isset($this->pageUrlsByPagePath[$actionName])) {
                foreach ($row->getColumns() as $name => $value) {
                    $this->pageUrlsByPagePath[$actionName]->setColumn($name, $value);
                }
            }

            if (isset($this->pageTitlesByPagePath[$actionName])) {
                foreach ($row->getColumns() as $name => $value) {
                    $this->pageTitlesByPagePath[$actionName]->setColumn($name, $value);
                }
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
        $table = $gaQuery->query($day, $dimensions = ['ga:hostname', 'ga:exitPagePath'], $exitPageMetrics, [
            'orderBys' => [
                ['field' => 'ga:sessions', 'order' => 'descending'],
                ['field' => 'ga:exitPagePath', 'order' => 'ascending']
            ],
        ]);

        foreach ($table->getRows() as $row) {
            $hostname = $row->getMetadata('ga:hostname');
            $actionName = 'http://' . $hostname . $row->getMetadata('ga:exitPagePath');

            $row->deleteColumn('label');

            if (isset($this->pageUrlsByPagePath[$actionName])) {
                foreach ($row->getColumns() as $name => $value) {
                    $this->pageUrlsByPagePath[$actionName]->setColumn($name, $value);
                }
            }

            if (isset($this->pageTitlesByPagePath[$actionName])) {
                foreach ($row->getColumns() as $name => $value) {
                    $this->pageTitlesByPagePath[$actionName]->setColumn($name, $value);
                }
            }
        }

        Common::destroy($table);
    }

    private function getPageTitlesRecord(Date $day)
    {
        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, $dimensions = ['ga:pageTitle', 'ga:hostname', 'ga:pagePath'], $this->getPageMetrics(), [
            'orderBys' => [
                ['field' => 'ga:pageviews', 'order' => 'descending'],
                ['field' => 'ga:pageTitle', 'order' => 'ascending']
            ],
        ]);

        foreach ($table->getRows() as $row) {
            $actionName = $row->getMetadata('ga:pageTitle');
            $actionRow = ArchivingHelper::getActionRow($actionName, Action::TYPE_PAGE_TITLE, $urlPrefix = '', $this->dataTables);

            $row->deleteColumn('label');

            $columns = $row->getColumns();
            foreach ($columns as $name => $value) {
                $actionRow->setColumn($name, $value);
            }

            $hostname = $row->getMetadata('ga:hostname');
            $url = 'http://' . $hostname . $row->getMetadata('ga:pagePath');
            $this->pageTitlesByPagePath[$url] = $actionRow;
        }

        Common::destroy($table);
    }

    private function getPageUrlsRecord(Date $day)
    {
        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, $dimensions = ['ga:hostname', 'ga:pagePath'], $this->getPageMetrics(), [
            'orderBys' => [
                ['field' => 'ga:pageviews', 'order' => 'descending'],
                ['field' => 'ga:pagePath', 'order' => 'ascending']
            ],
        ]);

        foreach ($table->getRows() as $row) {
            $hostname = $row->getMetadata('ga:hostname');
            $actionName = 'http://' . $hostname . $row->getMetadata('ga:pagePath');
            $actionRow = ArchivingHelper::getActionRow($actionName, Action::TYPE_PAGE_URL, $urlPrefix = '', $this->dataTables);

            $row->deleteColumn('label');

            $columns = $row->getColumns();
            foreach ($columns as $name => $value) {
                $actionRow->setColumn($name, $value);
            }

            $actionRow->setMetadata('url', $actionName);
            $this->pageUrlsByPagePath[$actionName] = $actionRow;
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
        $blob = $this->dataTables[$actionType]->getSerialized(ArchivingHelper::$maximumRowsInDataTableLevelZero, ArchivingHelper::$maximumRowsInSubDataTable,
            ArchivingHelper::$columnToSortByBeforeTruncation);
        $this->insertBlobRecord($recordName, $blob);
        unset($blob);
    }
}
