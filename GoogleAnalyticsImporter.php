<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter;

use Piwik\ArchiveProcessor\Parameters;
use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\DataAccess\RawLogDao;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Piwik;
use Piwik\Plugin\ViewDataTable;
use Piwik\Plugins\Referrers\API;
use Piwik\Site;
use Matomo\Dependencies\Monolog\Psr\Log\LoggerInterface;

class GoogleAnalyticsImporter extends \Piwik\Plugin
{
    const OPTION_ARCHIVING_FINISHED_FOR_SITE_PREFIX = 'GoogleAnalyticsImporter.archivingFinished.';

    private static $keywordMethods = [
        'Referrers.getKeywords',
        'Referrers.getKeywordsForPageUrl',
        'Referrers.getKeywordsForPageTitle',
        'Referrers.getKeywordsFromSearchEngineId',
        'Referrers.getKeywordsFromCampaignId',
    ];

    private static $unsupportedDataTableReports = [
        "Actions.getDownloads",
        "Actions.getDownload",
        "Actions.getOutlinks",
        "Actions.getOutlink",
        "Actions.getPageUrlsFollowingSiteSearch",
        "Actions.getPageTitlesFollowingSiteSearch",
        "Actions.getSiteSearchNoResultKeywords",
        "VisitTime.getVisitInformationPerLocalTime",
        "DevicesDetection.getBrowserEngines",
        "DevicePlugins.getPlugin",
        "UserId.getUsers",
        "Contents.getContentNames",
        "Contents.getContentPieces",
        "VisitorInterest.getNumberOfVisitsPerPage",
        "Provider.getProvider",
    ];

    public function registerEvents()
    {
        return [
            'AssetManager.getStylesheetFiles'        => 'getStylesheetFiles',
            'CronArchive.archiveSingleSite.finish' => 'archivingFinishedForSite',
            'Visualization.beforeRender' => 'configureImportedReportView',
            'Translate.getClientSideTranslationKeys' => 'getClientSideTranslationKeys',
            'API.Request.dispatch.end' => 'translateNotSetLabels',
            'SitesManager.deleteSite.end'            => 'onSiteDeleted',
            'Template.jsGlobalVariables' => 'addImportedDateRangesForSite',
            'Archiving.isRequestAuthorizedToArchive' => 'isRequestAuthorizedToArchive',
        ];
    }

    public function isRequestAuthorizedToArchive(&$isRequestAuthorizedToArchive, Parameters $params)
    {
        if (!$isRequestAuthorizedToArchive) { // if already false, don't need to do anything
            return;
        }

        if ($params->getPeriod()->getLabel() != 'day') {
            return;
        }

        /** @var ImportStatus $importStatus */
        $importStatus = StaticContainer::get(ImportStatus::class);

        $importedDateRange = $importStatus->getImportedDateRange($params->getSite()->getId());
        if (empty($importedDateRange)
            || empty(array_filter($importedDateRange))
        ) {
            return;
        }

        $isDayWithinImportedDateRange = $importStatus->isInImportedDateRange(
            $params->getPeriod()->getLabel(), $params->getPeriod()->getDateStart()->toString(), $params->getSite()->getId());
        if (!$isDayWithinImportedDateRange) {
            return;
        }

        $timezone = Site::getTimezoneFor($params->getSite()->getId());
        list($date1, $date2) = $this->getBoundsInTimezone($params->getPeriod(), $timezone);

        $dao = new RawLogDao();
        $hasVisits = $dao->hasSiteVisitsBetweenTimeframe($date1->getDatetime(), $date2->getDatetime(), $params->getSite()->getId());
        if ($hasVisits) {
            return;
        }

        StaticContainer::get(LoggerInterface::class)->debug(
            "GoogleAnalyticsImporter stopped day from being archived since it is in imported range and there is no raw log data. [idSite = {idSite}, period = {period}({date1} - {date2})]", [
                'idSite' => $params->getSite()->getId(),
                'period' => $params->getPeriod()->getLabel(),
                'date1' => $date1->getDatetime(),
                'date2' => $date2->getDatetime(),
            ]
        );

        $isRequestAuthorizedToArchive = false;
    }

    public function addImportedDateRangesForSite(&$out)
    {
        $importStatus = StaticContainer::get(ImportStatus::class);
        $range = $importStatus->getImportedSiteImportDateRange();
        if (empty($range)) {
            return;
        }

        list($startDate, $endDate) = $range;

        $out .= "\npiwik.importedFromGoogleStartDate = " . json_encode($startDate->toString()) . ";\n";
        $out .= "piwik.importedFromGoogleEndDate = " . json_encode($endDate->toString()) . ";\n";
    }

    public function onSiteDeleted($idSite)
    {
        $importStatus = StaticContainer::get(ImportStatus::class);
        try {
            $importStatus->deleteStatus($idSite);
        } catch (\Exception $ex) {
            // ignore
        }
    }

    public function getStylesheetFiles(&$stylesheets)
    {
        $stylesheets[] = "plugins/GoogleAnalyticsImporter/vue/src/ImportStatus/ImportStatus.less";
        $stylesheets[] = "plugins/GoogleAnalyticsImporter/stylesheets/styles.less";
    }

    public function getClientSideTranslationKeys(&$translationKeys)
    {
        $translationKeys[] = 'GoogleAnalyticsImporter_InvalidDateFormat';
        $translationKeys[] = 'GoogleAnalyticsImporter_LogDataRequiredForReport';
        $translationKeys[] = 'GoogleAnalyticsImporter_StartDate';
        $translationKeys[] = 'GoogleAnalyticsImporter_CreationDate';
        $translationKeys[] = 'GoogleAnalyticsImporter_StartDateHelp';
        $translationKeys[] = 'GoogleAnalyticsImporter_EndDate';
        $translationKeys[] = 'GoogleAnalyticsImporter_None';
        $translationKeys[] = 'GoogleAnalyticsImporter_EndDateHelp';
        $translationKeys[] = 'GoogleAnalyticsImporter_PropertyId';
        $translationKeys[] = 'GoogleAnalyticsImporter_PropertyIdGA4';
        $translationKeys[] = 'GoogleAnalyticsImporter_PropertyIdHelp';
        $translationKeys[] = 'GoogleAnalyticsImporter_PropertyIdGA4Help';
        $translationKeys[] = 'GoogleAnalyticsImporter_AccountId';
        $translationKeys[] = 'GoogleAnalyticsImporter_AccountIdHelp';
        $translationKeys[] = 'GoogleAnalyticsImporter_ViewId';
        $translationKeys[] = 'GoogleAnalyticsImporter_ViewIdHelp';
        $translationKeys[] = 'GoogleAnalyticsImporter_IsMobileApp';
        $translationKeys[] = 'GoogleAnalyticsImporter_IsMobileAppHelp';
        $translationKeys[] = 'GoogleAnalyticsImporter_Timezone';
        $translationKeys[] = 'GoogleAnalyticsImporter_Optional';
        $translationKeys[] = 'GoogleAnalyticsImporter_ExtraCustomDimensions';
        $translationKeys[] = 'GoogleAnalyticsImporter_ForceCustomDimensionSlotCheck';
        $translationKeys[] = 'GoogleAnalyticsImporter_IsVerboseLoggingEnabled';
        $translationKeys[] = 'GoogleAnalyticsImporter_IsVerboseLoggingEnabledHelp';
        $translationKeys[] = 'GoogleAnalyticsImporter_ScheduleImportDesc1';
        $translationKeys[] = 'GoogleAnalyticsImporter_ScheduleImportDesc2';
        $translationKeys[] = 'GoogleAnalyticsImporter_TimezoneHelp';
        $translationKeys[] = 'GoogleAnalyticsImporter_TimezoneGA4Help';
        $translationKeys[] = 'GoogleAnalyticsImporter_ExtraCustomDimensionsHelp';
        $translationKeys[] = 'GoogleAnalyticsImporter_ExtraCustomDimensionsGA4Help';
        $translationKeys[] = 'GoogleAnalyticsImporter_ForceCustomDimensionSlotCheckHelp';
        $translationKeys[] = 'GoogleAnalyticsImporter_Troubleshooting';
        $translationKeys[] = 'GoogleAnalyticsImporter_Start';
        $translationKeys[] = 'GoogleAnalyticsImporter_RateLimitHelp';
        $translationKeys[] = 'GoogleAnalyticsImporter_RateLimitHourlyHelp';
        $translationKeys[] = 'GoogleAnalyticsImporter_KilledStatusHelp';
        $translationKeys[] = 'GoogleAnalyticsImporter_ResumeDesc';
        $translationKeys[] = 'GoogleAnalyticsImporter_MatomoSite';
        $translationKeys[] = 'GoogleAnalyticsImporter_GoogleAnalyticsInfo';
        $translationKeys[] = 'GoogleAnalyticsImporter_Status';
        $translationKeys[] = 'GoogleAnalyticsImporter_LatestDayProcessed';
        $translationKeys[] = 'GoogleAnalyticsImporter_ScheduledReImports';
        $translationKeys[] = 'GoogleAnalyticsImporter_StartFinishTimes';
        $translationKeys[] = 'GoogleAnalyticsImporter_Actions';
        $translationKeys[] = 'GoogleAnalyticsImporter_SiteDeleted';
        $translationKeys[] = 'GoogleAnalyticsImporter_SiteID';
        $translationKeys[] = 'GoogleAnalyticsImporter_FinishedImportingDaysWaiting';
        $translationKeys[] = 'GoogleAnalyticsImporter_ErrorMessage';
        $translationKeys[] = 'GoogleAnalyticsImporter_ErrorMessageBugReportRequest';
        $translationKeys[] = 'GoogleAnalyticsImporter_LastDayImported';
        $translationKeys[] = 'GoogleAnalyticsImporter_LastDayArchived';
        $translationKeys[] = 'GoogleAnalyticsImporter_ImportStartDate';
        $translationKeys[] = 'GoogleAnalyticsImporter_ImportEndDate';
        $translationKeys[] = 'GoogleAnalyticsImporter_EditEndDate';
        $translationKeys[] = 'GoogleAnalyticsImporter_ReimportDate';
        $translationKeys[] = 'GoogleAnalyticsImporter_ImportStartTime';
        $translationKeys[] = 'GoogleAnalyticsImporter_LastResumeTime';
        $translationKeys[] = 'GoogleAnalyticsImporter_TimeFinished';
        $translationKeys[] = 'GoogleAnalyticsImporter_ThisJobShouldFinishToday';
        $translationKeys[] = 'GoogleAnalyticsImporter_EstimatedFinishIn';
        $translationKeys[] = 'GoogleAnalyticsImporter_JobWillRunUntilManuallyCancelled';
        $translationKeys[] = 'General_Unknown';
        $translationKeys[] = 'GoogleAnalyticsImporter_EnterImportDateRange';
        $translationKeys[] = 'GoogleAnalyticsImporter_Schedule';
        $translationKeys[] = 'GoogleAnalyticsImporter_EnterImportEndDate';
        $translationKeys[] = 'GoogleAnalyticsImporter_LeaveEmptyToRemove';
        $translationKeys[] = 'GoogleAnalyticsImporter_Change';
        $translationKeys[] = 'GoogleAnalyticsImporter_SelectImporterUAInlineHelp';
        $translationKeys[] = 'GoogleAnalyticsImporter_SelectImporterGA4InlineHelp';
        $translationKeys[] = 'GoogleAnalyticsImporter_MaxEndDateHelp';
    }

    public function translateNotSetLabels(&$returnedValue, $params)
    {
        if (!($returnedValue instanceof DataTable\DataTableInterface)) {
            return;
        }

        $translation = Piwik::translate('GoogleAnalyticsImporter_NotSetInGA');
        $labelToLookFor = RecordImporter::NOT_SET_IN_GA_LABEL;

        $method = Common::getRequestVar('method');
        if (in_array($method, self::$keywordMethods)) {
            $translation = API::getKeywordNotDefinedString();
            $labelToLookFor = '(not provided)';
        }

        $returnedValue->filter(function (DataTable $table) use ($translation, $labelToLookFor) {
            $row = $table->getRowFromLabel($labelToLookFor);
            if (!empty($row)) {
                $row->setColumn('label', $translation);
            }

            foreach ($table->getRows() as $childRow) {
                $subtable = $childRow->getSubtable();
                if ($subtable) {
                    $this->translateNotSetLabels($subtable, []);
                }
            }
        });
    }

    public function configureImportedReportView(ViewDataTable $view)
    {
        $table = $view->getDataTable();
        if (empty($table)
            || !($table instanceof DataTable)
        ) {
            return;
        }

        $period = Common::getRequestVar('period', false);
        $date = Common::getRequestVar('date', false);
        if (empty($period) || empty($date)) {
            return;
        }

        $module = Common::getRequestVar('module');
        $action = Common::getRequestVar('action');

        $importStatus = StaticContainer::get(ImportStatus::class);
        if ($module == 'Live'
            || ($module == 'Ecommerce' && $action == 'getEcommerceLog')
        ) {
            if ($table->getRowsCount() > 0
                || !$importStatus->isInImportedDateRange($period, $date)
            ) {
                return;
            }

            $this->addNewlineIfNeededToFooter($view);
            $view->config->show_footer_message .= '<br/>' . Piwik::translate('GoogleAnalyticsImporter_LiveDataUnavailableForImported');
            return;
        }

        // check for unsupported reports
        $method = "$module.$action";
        if (in_array($method, self::$unsupportedDataTableReports)) {
            if ($table->getRowsCount() > 0
                || !$importStatus->isInImportedDateRange($period, $date)
            ) {
                return;
            }

            $this->addNewlineIfNeededToFooter($view);
            $view->config->show_footer_message .= '<br/>' . Piwik::translate('GoogleAnalyticsImporter_UnsupportedReportInImportRange');
            return;
        }

        // check report based on period
        if ($period === 'day') {
            // for day periods we can tell if the report is in GA through metadata
            $isImportedFromGoogle = $table->getMetadata(RecordImporter::IS_IMPORTED_FROM_GOOGLE_METADATA_NAME);
            if (!$isImportedFromGoogle) {
                return;
            }

            $this->addNewlineIfNeededToFooter($view);
            $view->config->show_footer_message .= '<br/>' . Piwik::translate('GoogleAnalyticsImporter_ThisReportWasImportedFromGoogle');
        } else {
            // for non-day periods, we can't tell if the report is all GA data or mixed, so we guess based on
            // whether the data is in the imported date range
            if (!$importStatus->isInImportedDateRange($period, $date)) {
                return;
            }

            $this->addNewlineIfNeededToFooter($view);
            $view->config->show_footer_message .= '<br/>' . Piwik::translate('GoogleAnalyticsImporter_ThisReportWasImportedFromGoogleMultiPeriod');
        }
    }

    private function addNewlineIfNeededToFooter(ViewDataTable $view)
    {
        if (!empty($view->config->show_footer_message)) {
            $view->config->show_footer_message .= '<br/>';
        }
    }
    public function archivingFinishedForSite($idSite, $completed)
    {
        /** @var LoggerInterface $logger */
        $logger = StaticContainer::get(LoggerInterface::class);

        /** @var ImportStatus $importStatus */
        $importStatus = StaticContainer::get(ImportStatus::class);

        try {
            $importStatus->getImportStatus($idSite);
        } catch (\Exception $ex) {
            return;
        }

        $dateFinished = getenv(Tasks::DATE_FINISHED_ENV_VAR);
        if (empty($dateFinished)) {
            $logger->debug('Archiving for imported site was finished, but date environment variable not set. Cannot mark day as complete.');
            return;
        }

        $dateFinished = Date::factory($dateFinished);

        if (!$completed) {
            $logger->info("Archiving for imported site (ID = {idSite}) was not completed successfully. Will try again next run.", [
                'idSite' => $idSite,
            ]);
            return;
        }

        $importStatus->importArchiveFinished($idSite, $dateFinished);
    }

    private function getBoundsInTimezone(\Piwik\Period $period, $timezone)
    {
        $date1 = $period->getDateTimeStart()->setTimezone($timezone);
        $date2 = $period->getDateTimeEnd()->setTimezone($timezone);

        return [$date1, $date2];
    }
}
