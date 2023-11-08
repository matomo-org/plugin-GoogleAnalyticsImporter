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
use Piwik\Http;
use Piwik\Period;
use Piwik\Piwik;
use Piwik\Plugin\Manager;
use Piwik\Plugin\ViewDataTable;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\Authorization;
use Piwik\Plugins\ConnectAccounts\ConnectAccounts;
use Piwik\Plugins\Referrers\API;
use Piwik\Plugins\ConnectAccounts\helpers\ConnectHelper;
use Piwik\Plugins\ConnectAccounts\Strategy\Google\GoogleConnect;
use Piwik\Plugins\UsersManager\UserPreferences;
use Piwik\Request;
use Piwik\SettingsPiwik;
use Piwik\Url;
use Piwik\Site;
use Piwik\Log\LoggerInterface;
class GoogleAnalyticsImporter extends \Piwik\Plugin
{
    const OPTION_ARCHIVING_FINISHED_FOR_SITE_PREFIX = 'GoogleAnalyticsImporter.archivingFinished.';
    private static $keywordMethods = ['Referrers.getKeywords', 'Referrers.getKeywordsForPageUrl', 'Referrers.getKeywordsForPageTitle', 'Referrers.getKeywordsFromSearchEngineId', 'Referrers.getKeywordsFromCampaignId'];
    private static $unsupportedDataTableReports = ["Actions.getDownloads", "Actions.getDownload", "Actions.getOutlinks", "Actions.getOutlink", "Actions.getPageUrlsFollowingSiteSearch", "Actions.getPageTitlesFollowingSiteSearch", "Actions.getSiteSearchNoResultKeywords", "VisitTime.getVisitInformationPerLocalTime", "DevicesDetection.getBrowserEngines", "DevicePlugins.getPlugin", "UserId.getUsers", "Contents.getContentNames", "Contents.getContentPieces", "VisitorInterest.getNumberOfVisitsPerPage", "Provider.getProvider"];
    public function registerEvents()
    {
        return ['AssetManager.getStylesheetFiles' => 'getStylesheetFiles', 'CronArchive.archiveSingleSite.finish' => 'archivingFinishedForSite', 'Visualization.beforeRender' => 'configureImportedReportView', 'Translate.getClientSideTranslationKeys' => 'getClientSideTranslationKeys', 'API.Request.dispatch.end' => 'translateNotSetLabels', 'SitesManager.deleteSite.end' => 'onSiteDeleted', 'Template.jsGlobalVariables' => 'addImportedDateRangesForSite', 'Archiving.isRequestAuthorizedToArchive' => 'isRequestAuthorizedToArchive', 'AssetManager.getJavaScriptFiles' => 'getJsFiles', 'GoogleAnalyticsImporter.getGoogleConfigComponentExtensions' => 'getGoogleConfigComponent'];
    }
    public function isRequestAuthorizedToArchive(&$isRequestAuthorizedToArchive, Parameters $params)
    {
        if (!$isRequestAuthorizedToArchive) {
            // if already false, don't need to do anything
            return;
        }
        if ($params->getPeriod()->getLabel() != 'day') {
            return;
        }
        /** @var ImportStatus $importStatus */
        $importStatus = StaticContainer::get(\Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus::class);
        $importedDateRange = $importStatus->getImportedDateRange($params->getSite()->getId());
        if (empty($importedDateRange) || empty(array_filter($importedDateRange))) {
            return;
        }
        $isDayWithinImportedDateRange = $importStatus->isInImportedDateRange($params->getPeriod()->getLabel(), $params->getPeriod()->getDateStart()->toString(), $params->getSite()->getId());
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
        StaticContainer::get(LoggerInterface::class)->debug("GoogleAnalyticsImporter stopped day from being archived since it is in imported range and there is no raw log data. [idSite = {idSite}, period = {period}({date1} - {date2})]", ['idSite' => $params->getSite()->getId(), 'period' => $params->getPeriod()->getLabel(), 'date1' => $date1->getDatetime(), 'date2' => $date2->getDatetime()]);
        $isRequestAuthorizedToArchive = \false;
    }
    public function addImportedDateRangesForSite(&$out)
    {
        $importStatus = StaticContainer::get(\Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus::class);
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
        $importStatus = StaticContainer::get(\Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus::class);
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
        if (Manager::getInstance()->isPluginActivated('ConnectAccounts') && ConnectAccounts::isMatomoOAuthEnabled()) {
            $stylesheets[] = "plugins/ConnectAccounts/vue/src/Configure/ConfigureConnection.less";
        } else {
            $stylesheets[] = "plugins/GoogleAnalyticsImporter/vue/src/Configure/ConfigureConnection.less";
        }
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
        $translationKeys[] = 'GoogleAnalyticsImporter_CloudRateLimitHelp';
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
        $translationKeys[] = 'GoogleAnalyticsImporter_ConfigureTheImporter';
        $translationKeys[] = 'GoogleAnalyticsImporter_ImporterIsConfigured';
        $translationKeys[] = 'GoogleAnalyticsImporter_ReAuthorize';
        $translationKeys[] = 'GoogleAnalyticsImporter_ClientConfigSuccessfullyUpdated';
        $translationKeys[] = 'GoogleAnalyticsImporter_Authorize';
        $translationKeys[] = 'GoogleAnalyticsImporter_ConfigureClientDesc1';
        $translationKeys[] = 'GoogleAnalyticsImporter_ConfigureClientDesc2';
        $translationKeys[] = 'GoogleAnalyticsImporter_ConfigurationFile';
        $translationKeys[] = 'GoogleAnalyticsImporter_ConfigurationText';
        $translationKeys[] = 'GoogleAnalyticsImporter_RemoveClientConfiguration';
        $translationKeys[] = 'GoogleAnalyticsImporter_DeleteUploadedClientConfig';
        $translationKeys[] = 'General_Remove';
        $translationKeys[] = 'General_Save';
        $translationKeys[] = 'GoogleAnalyticsImporter_SettingUp';
        $translationKeys[] = 'GoogleAnalyticsImporter_ImporterHelp1';
        $translationKeys[] = 'GoogleAnalyticsImporter_ImporterHelp2';
        $translationKeys[] = 'GoogleAnalyticsImporter_ImporterHelp3';
        $translationKeys[] = 'GoogleAnalyticsImporter_ScheduleAnImport';
        $translationKeys[] = 'GoogleAnalyticsImporter_ImportJobs';
        $translationKeys[] = 'GoogleAnalyticsImporter_ThereAreNoImportJobs';
        $translationKeys[] = 'GoogleAnalyticsImporter_CancelJobConfirm';
        $translationKeys[] = 'General_Yes';
        $translationKeys[] = 'General_No';
        $translationKeys[] = 'GoogleAnalyticsImporter_SelectImporterUAInlineHelp';
        $translationKeys[] = 'GoogleAnalyticsImporter_SelectImporterUAInlineHelpText';
        $translationKeys[] = 'GoogleAnalyticsImporter_SelectImporterGA4InlineHelp';
        $translationKeys[] = 'GoogleAnalyticsImporter_SelectImporterGA4InlineHelpText';
        $translationKeys[] = 'GoogleAnalyticsImporter_SelectImporter';
        $translationKeys[] = 'GoogleAnalyticsImporter_SelectImporterSelection';
        $translationKeys[] = 'GoogleAnalyticsImporter_ScheduleAnImportGA4';
        $translationKeys[] = 'GoogleAnalyticsImporter_MaxEndDateHelp';
        $translationKeys[] = 'GoogleAnalyticsImporter_PendingGAImportReportNotificationNoData';
        $translationKeys[] = 'GoogleAnalyticsImporter_PendingGAImportReportNotificationSomeData';
        $translationKeys[] = 'GoogleAnalyticsImporter_NoDateSuccessImportMessageLine1';
        $translationKeys[] = 'GoogleAnalyticsImporter_NoDateSuccessImportMessageLine2';
        $translationKeys[] = 'GoogleAnalyticsImporter_OauthFailedMessage';
        $translationKeys[] = 'GoogleAnalyticsImporter_ConfigureImportNotificationMessage';
        $translationKeys[] = 'GoogleAnalyticsImporter_ConfigureTheImporterHelp';
        $translationKeys[] = 'GoogleAnalyticsImporter_ConfigureTheImporterHelpNewDate';
        $translationKeys[] = 'GoogleAnalyticsImporter_ConfigureTheImporterLabel1';
        $translationKeys[] = 'GoogleAnalyticsImporter_ConfigureTheImporterLabel2';
        $translationKeys[] = 'GoogleAnalyticsImporter_ConfigureTheImporterLabel3';
        $translationKeys[] = 'General_Upload';
        $translationKeys[] = 'GoogleAnalyticsImporter_Uploading';
        $translationKeys[] = 'GoogleAnalyticsImporter_FutureDateHelp';
        $translationKeys[] = 'GoogleAnalyticsImporter_ScheduleImportDescription';
        $translationKeys[] = 'GoogleAnalyticsImporter_EndDateHelpText';
        $translationKeys[] = 'GoogleAnalyticsImporter_AdminMenuTitle';
        $translationKeys[] = 'GoogleAnalyticsImporter_Authorize';
        $translationKeys[] = 'GoogleAnalyticsImporter_GoogleOauthCompleteWarning';
        $translationKeys[] = 'GoogleAnalyticsImporter_GAImportNoDataScreenStep01';
        $translationKeys[] = 'GoogleAnalyticsImporter_GAImportNoDataScreenStep02';
        $translationKeys[] = 'GoogleAnalyticsImporter_GAImportNoDataScreenStep03';
        $translationKeys[] = 'GoogleAnalyticsImporter_GAImportNoDataScreenStep04';
        $translationKeys[] = 'GoogleAnalyticsImporter_GAImportNoDataScreenStep05';
        $translationKeys[] = 'GoogleAnalyticsImporter_GAImportNoDataScreenStep06';
        $translationKeys[] = 'GoogleAnalyticsImporter_GAImportNoDataScreenStep07';
        $translationKeys[] = 'GoogleAnalyticsImporter_GAImportNoDataScreenStep07Note';
        $translationKeys[] = 'GoogleAnalyticsImporter_GAImportNoDataScreenStep08';
        $translationKeys[] = 'GoogleAnalyticsImporter_GAImportNoDataScreenStep09';
        $translationKeys[] = 'GoogleAnalyticsImporter_Start';
        $translationKeys[] = 'GoogleAnalyticsImporter_ReAuthorize';
        $translationKeys[] = 'GoogleAnalyticsImporter_AccountsConnectedSuccessfully';
        $translationKeys[] = 'GoogleAnalyticsImporter_UploadSuccessful';
        $translationKeys[] = 'GoogleAnalyticsImporter_StreamIdFilter';
        $translationKeys[] = 'GoogleAnalyticsImporter_StreamIdFilterHelpText';
        if (Manager::getInstance()->isPluginActivated('ConnectAccounts') && ConnectAccounts::isMatomoOAuthEnabled()) {
            $translationKeys[] = "ConnectAccounts_ConfigureGoogleAuthHelp1";
            $translationKeys[] = "ConnectAccounts_ConfigureGoogleAuthHelp2";
            $translationKeys[] = "ConnectAccounts_OptionQuickConnectWithGa";
            $translationKeys[] = "ConnectAccounts_OptionAdvancedConnectWithGa";
        }
    }
    public function getJsFiles(&$files)
    {
        $files[] = "plugins/GoogleAnalyticsImporter/javascripts/googleAnalyticsImporter.js";
        $files[] = "plugins/GoogleAnalyticsImporter/javascripts/configureImportNotification.js";
    }
    public function translateNotSetLabels(&$returnedValue, $params)
    {
        if (!$returnedValue instanceof DataTable\DataTableInterface) {
            return;
        }
        $translation = Piwik::translate('GoogleAnalyticsImporter_NotSetInGA');
        $labelToLookFor = \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporter::NOT_SET_IN_GA_LABEL;
        $method = Common::getRequestVar('method');
        if (in_array($method, self::$keywordMethods)) {
            $translation = API::getKeywordNotDefinedString();
            $labelToLookFor = '(not provided)';
        }
        $returnedValue->filter(function (DataTable $table) use($translation, $labelToLookFor) {
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
        if (empty($table) || !$table instanceof DataTable) {
            return;
        }
        $period = Common::getRequestVar('period', \false);
        $date = Common::getRequestVar('date', \false);
        if (empty($period) || empty($date)) {
            return;
        }
        $module = Common::getRequestVar('module');
        $action = Common::getRequestVar('action');
        $importStatus = StaticContainer::get(\Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus::class);
        if ($module == 'Live' || $module == 'Ecommerce' && $action == 'getEcommerceLog') {
            if ($table->getRowsCount() > 0 || !$importStatus->isInImportedDateRange($period, $date)) {
                return;
            }
            $this->addNewlineIfNeededToFooter($view);
            $view->config->show_footer_message .= '<br/>' . Piwik::translate('GoogleAnalyticsImporter_LiveDataUnavailableForImported');
            return;
        }
        // check for unsupported reports
        $method = "{$module}.{$action}";
        if (in_array($method, self::$unsupportedDataTableReports)) {
            if ($table->getRowsCount() > 0 || !$importStatus->isInImportedDateRange($period, $date)) {
                return;
            }
            $this->addNewlineIfNeededToFooter($view);
            $view->config->show_footer_message .= '<br/>' . Piwik::translate('GoogleAnalyticsImporter_UnsupportedReportInImportRange');
            return;
        }
        // check report based on period
        if ($period === 'day') {
            // for day periods we can tell if the report is in GA through metadata
            $isImportedFromGoogle = $table->getMetadata(\Piwik\Plugins\GoogleAnalyticsImporter\RecordImporter::IS_IMPORTED_FROM_GOOGLE_METADATA_NAME);
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
        $importStatus = StaticContainer::get(\Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus::class);
        try {
            $importStatus->getImportStatus($idSite);
        } catch (\Exception $ex) {
            return;
        }
        $dateFinished = getenv(\Piwik\Plugins\GoogleAnalyticsImporter\Tasks::DATE_FINISHED_ENV_VAR);
        if (empty($dateFinished)) {
            $logger->debug('Archiving for imported site was finished, but date environment variable not set. Cannot mark day as complete.');
            return;
        }
        $dateFinished = Date::factory($dateFinished);
        if (!$completed) {
            $logger->info("Archiving for imported site (ID = {idSite}) was not completed successfully. Will try again next run.", ['idSite' => $idSite]);
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
    public static function datesOverlap($periods, $start_time_key = 'start_time', $end_time_key = 'end_time')
    {
        // order periods by start_time
        usort($periods, function ($a, $b) use($start_time_key, $end_time_key) {
            return strtotime($a[$start_time_key]) <=> strtotime($b[$end_time_key]);
        });
        // check two periods overlap
        foreach ($periods as $key => $period) {
            if ($key != 0) {
                if (strtotime($period[$start_time_key]) < strtotime($periods[$key - 1][$end_time_key])) {
                    return \true;
                }
            }
        }
        return \false;
    }
    /**
     * Check if there are pending imports, and if so, if the report date is in the range of the dates of the import
     * @return bool
     * @throws \Exception
     */
    public static function canDisplayImportPendingNotice() : array
    {
        $isGASite = \false;
        $instance = new \Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus();
        $currentIdSite = Common::getRequestVar('idSite', -1);
        try {
            $status = $instance->getImportStatus($currentIdSite);
            $isGASite = !empty($status);
        } catch (\Exception $exception) {
            return ['displayPending' => \false, 'isGASite' => $isGASite];
        }
        if (!self::hasOverLapCheckParams()) {
            return ['displayPending' => \false, 'isGASite' => $isGASite];
        }
        if ($currentIdSite === -1) {
            return ['displayPending' => \false, 'isGASite' => $isGASite];
        }
        if ($status['status'] != \Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus::STATUS_ONGOING) {
            return ['displayPending' => \false, 'isGASite' => $isGASite];
        }
        $periods = [
            //Report Dates
            ['start_time' => Date::factory(Period\Factory::makePeriodFromQueryParams('', Common::getRequestVar('period'), Common::getRequestVar('date'))->getDateStart()), 'end_time' => Date::factory(Period\Factory::makePeriodFromQueryParams('', Common::getRequestVar('period'), Common::getRequestVar('date'))->getDateEnd())],
            //Import Dates
            [
                'start_time' => Date::factory($status['import_range_start'] ?? 'now'),
                //due to null it sets an error, can be an edge case where someone requests a report before setImportedDateRange is called but import is created
                'end_time' => Date::factory($status['import_range_end'] ?? 'now'),
            ],
        ];
        if (self::datesOverlap($periods)) {
            $importedRange = $instance->getImportedDateRange($currentIdSite);
            return ['displayPending' => \true, 'availableDate' => $importedRange[0], 'isGASite' => $isGASite];
        }
        return ['displayPending' => \false, 'isGASite' => $isGASite];
    }
    public static function hasOverLapCheckParams()
    {
        if (!Common::getRequestVar('period', \false) || !Common::getRequestVar('date', \false) || !Common::getRequestVar('idSite')) {
            return \false;
        }
        return \true;
    }
    public function getGoogleConfigComponent(&$componentExtensions, $isNoDataPage)
    {
        $authorization = StaticContainer::get(Authorization::class);
        if (!$authorization->hasClientConfiguration() || $isNoDataPage) {
            // Only set index 0 if it hasn't already been set, since we want ConnectAccounts to take precedence
            $componentExtensions[0] = $componentExtensions[0] ?? ['plugin' => 'GoogleAnalyticsImporter', 'component' => 'ConfigureConnection'];
        }
        return $componentExtensions;
    }
    public static function isConnectAccountsPluginActivated()
    {
        return Manager::getInstance()->isPluginActivated('ConnectAccounts') && ConnectAccounts::isMatomoOAuthEnabled();
    }
    public static function getConfigureConnectProps($nonce)
    {
        $isConnectAccountsActivated = self::isConnectAccountsPluginActivated();
        $authBaseUrl = $isConnectAccountsActivated ? "https://" . StaticContainer::get('CloudAccountsInstanceId') . '/index.php?' : '';
        $jwt = Common::getRequestVar('state', '', 'string');
        if (empty($jwt) && Piwik::hasUserSuperUserAccess() && $isConnectAccountsActivated) {
            // verify an existing user by supplying a jwt too
            $jwt = ConnectHelper::buildOAuthStateJwt(SettingsPiwik::getPiwikInstanceId(), ConnectAccounts::INITIATED_BY_GA);
        }
        $googleAuthUrl = '';
        if ($isConnectAccountsActivated) {
            $googleAuthUrl = $authBaseUrl . Http::buildQuery(['module' => 'ConnectAccounts', 'action' => 'initiateOauth', 'state' => $jwt, 'strategy' => GoogleConnect::getStrategyName()]);
        }
        $idSite = Request::fromRequest()->getIntegerParameter('idSite', 0);
        // If for some reason the idSite query parameter isn't set, look up the default site ID
        if ($idSite < 1) {
            $idSite = StaticContainer::get(UserPreferences::class)->getDefaultWebsiteId();
        }
        return ['isConnectAccountsActivated' => $isConnectAccountsActivated, 'primaryText' => Piwik::translate('GoogleAnalyticsImporter_ConfigureTheImporterLabel1'), 'radioOptions' => !$isConnectAccountsActivated ? [] : ['connectAccounts' => Piwik::translate('ConnectAccounts_OptionQuickConnectWithGa'), 'manual' => Piwik::translate('ConnectAccounts_OptionAdvancedConnectWithGa')], 'googleAuthUrl' => $googleAuthUrl, 'manualConfigText' => Piwik::translate('GoogleAnalyticsImporter_ConfigureTheImporterLabel2') . '<br />' . Piwik::translate('GoogleAnalyticsImporter_ConfigureTheImporterLabel3', ['<a href="'.Url::addCampaignParametersToMatomoLink('https://matomo.org/faq/general/set-up-google-analytics-import/').'" rel="noreferrer noopener" target="_blank">', '</a>']), 'manualConfigNonce' => $nonce, 'manualActionUrl' => Url::getCurrentUrlWithoutQueryString() . '?' . Http::buildQuery(['module' => 'GoogleAnalyticsImporter', 'action' => 'configureClient', 'idSite' => $idSite]), 'connectAccountsUrl' => $googleAuthUrl, 'connectAccountsBtnText' => Piwik::translate('ConnectAccounts_ConnectWithGoogleText'), 'additionalHelpText' => Piwik::translate('GoogleAnalyticsImporter_ConfigureTheImporterHelpNewDate', ['<strong>', '</strong>'])];
    }
}
