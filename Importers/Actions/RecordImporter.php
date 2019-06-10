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
use Piwik\Plugins\Actions\Archiver;
use Piwik\Plugins\Actions\ArchivingHelper;

class RecordImporter extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporter
{
    const PLUGIN_NAME = 'Actions';

    public function queryGoogleAnalyticsApi(Date $day)
    {
        ArchivingHelper::reloadConfig();

        // page titles record
        $pageTitlesRecord = $this->getPageTitlesRecord($day, ArchivingHelper::$maximumRowsInDataTableLevelZero);
        $blob = $pageTitlesRecord->getSerialized(ArchivingHelper::$maximumRowsInDataTableLevelZero, ArchivingHelper::$maximumRowsInSubDataTable,
            ArchivingHelper::$columnToSortByBeforeTruncation);
        $this->insertBlobRecord(Archiver::PAGE_TITLES_RECORD_NAME, $blob);
        Common::destroy($pageTitlesRecord);

        unset($blob);

        // TODO: other repoprts
    }

    private function getPageTitlesRecord(Date $day, $maxAllowedRows)
    {
        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, $dimensions = ['ga:pageTitle'], $this->getActionsMetrics(), [
            'orderBys' => [
                ['field' => 'ga:pageviews', 'order' => 'descending'],
                ['field' => 'ga:pageTitle', 'order' => 'ascending']
            ],
        ]);
        // TODO: must handle actions metrics in service

        $record = new DataTable();
        $record->setMaximumAllowedRows($maxAllowedRows);
        foreach ($table->getRows() as $row) {
            // TODO
        }
        return $record;
    }
}

/*
records:
    const DOWNLOADS_RECORD_NAME = 'Actions_downloads';
    const OUTLINKS_RECORD_NAME = 'Actions_outlink';
    const PAGE_TITLES_RECORD_NAME = 'Actions_actions';
    const SITE_SEARCH_RECORD_NAME = 'Actions_sitesearch';
    const PAGE_URLS_RECORD_NAME = 'Actions_actions_url';

actions_actions
 */