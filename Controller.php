<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter;

use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\DataTable\Renderer\Json;
use Piwik\Date;
use Piwik\Http;
use Piwik\Nonce;
use Piwik\Notification;
use Piwik\Piwik;
use Piwik\Plugin\Manager;
use Piwik\Plugins\ConnectAccounts\ConnectAccounts;
use Piwik\Plugins\ConnectAccounts\helpers\ConnectHelper;
use Piwik\Plugins\ConnectAccounts\Strategy\Google\GoogleConnect;
use Piwik\Plugins\GoogleAnalyticsImporter\Commands\ImportGA4Reports;
use Piwik\Plugins\GoogleAnalyticsImporter\Commands\ImportReports;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\Authorization;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\AuthorizationGA4;
use Piwik\Plugins\GoogleAnalyticsImporter\Input\EndDate;
use Piwik\Plugins\MobileAppMeasurable\Type;
use Piwik\Plugins\SitesManager\SiteContentDetection\GoogleAnalytics3;
use Piwik\Plugins\SitesManager\SiteContentDetection\GoogleAnalytics4;
use Piwik\Site;
use Piwik\SettingsPiwik;
use Piwik\Url;
use Piwik\Log\LoggerInterface;
use Piwik\Plugins\SitesManager\SitesManager;
class Controller extends \Piwik\Plugin\ControllerAdmin
{
    const OAUTH_STATE_NONCE_NAME = 'GoogleAnalyticsImporter.oauthStateNonce';
    public function index($errorMessage = \false)
    {
        Piwik::checkUserHasSuperUserAccess();
        $errorMessage = $errorMessage ?: Common::getRequestVar('error', '');
        if (!empty($errorMessage)) {
            if ($errorMessage === 'access_denied') {
                $errorMessage = Piwik::translate('GoogleAnalyticsImporter_OauthFailedMessage');
            } elseif ($errorMessage === 'jwt_validation_error') {
                $errorMessage = Piwik::translate('General_ExceptionSecurityCheckFailed');
            }
            $notification = new Notification($errorMessage);
            $notification->context = Notification::CONTEXT_ERROR;
            $notification->type = Notification::TYPE_TRANSIENT;
            Notification\Manager::notify('configureerror', $notification);
        }
        /** @var Authorization $authorization */
        $authorization = StaticContainer::get(Authorization::class);
        $authUrl = null;
        $nonce = null;
        $hasClientConfiguration = $authorization->hasClientConfiguration();
        if ($hasClientConfiguration) {
            try {
                $googleClient = $authorization->getConfiguredClient();
            } catch (\Exception $ex) {
                $authorization->deleteClientConfiguration();
                throw $ex;
            }
            $authUrl = $googleClient->createAuthUrl();
            $nonce = Nonce::getNonce('GoogleAnalyticsImporter.deleteGoogleClientConfig', 1200);
        } else {
            $nonce = Nonce::getNonce('GoogleAnalyticsImporter.googleClientConfig', 1200);
        }
        $importStatus = StaticContainer::get(\Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus::class);
        $statuses = $importStatus->getAllImportStatuses($checkKilledStatus = \true);
        foreach ($statuses as &$status) {
            if (isset($status['site']) && $status['site'] instanceof Site) {
                $status['site'] = ['idsite' => $status['site']->getId(), 'name' => $status['site']->getName()];
            }
        }
        $stopImportNonce = Nonce::getNonce('GoogleAnalyticsImporter.stopImportNonce', 1200);
        $startImportNonce = Nonce::getNonce('GoogleAnalyticsImporter.startImportNonce', 1200);
        $changeImportEndDateNonce = Nonce::getNonce('GoogleAnalyticsImporter.changeImportEndDateNonce', 1200);
        $resumeImportNonce = Nonce::getNonce('GoogleAnalyticsImporter.resumeImportNonce', 1200);
        $scheduleReImportNonce = Nonce::getNonce('GoogleAnalyticsImporter.scheduleReImport', 1200);
        $maxEndDateDesc = null;
        $endDate = StaticContainer::get(EndDate::class);
        $maxEndDate = $endDate->getConfiguredMaxEndDate();
        if ($maxEndDate == 'today' || $maxEndDate == 'now') {
            $maxEndDateDesc = Piwik::translate('GoogleAnalyticsImporter_TodaysDate');
        } else {
            if ($maxEndDate == 'yesterday' || $maxEndDate == 'yesterdaySameTime') {
                $maxEndDateDesc = Piwik::translate('GoogleAnalyticsImporter_YesterdaysDate');
            } else {
                if (!empty($maxEndDate)) {
                    $maxEndDateDesc = Date::factory($maxEndDate)->toString();
                }
            }
        }
        $isConnectAccountsActivated = \Piwik\Plugins\GoogleAnalyticsImporter\GoogleAnalyticsImporter::isConnectAccountsPluginActivated();
        if ($isConnectAccountsActivated) {
            $notification = new Notification(Piwik::translate('GoogleAnalyticsImporter_GoogleOauthCompleteWarning', ['<strong>', '</strong>']));
            $notification->context = Notification::CONTEXT_WARNING;
            $notification->raw = \true;
            $notification->flags = Notification::FLAG_CLEAR;
            Notification\Manager::notify('GoogleAnalyticsImporter_OauthCompletionWarning', $notification);
        }
        $configureConnectionProps = \Piwik\Plugins\GoogleAnalyticsImporter\GoogleAnalyticsImporter::getConfigureConnectProps($nonce);
        $isClientConfigurable = StaticContainer::get('GoogleAnalyticsImporter.isClientConfigurable');
        return $this->renderTemplate('index', ['isClientConfigurable' => $isClientConfigurable, 'isConfigured' => $authorization->hasAccessToken(), 'auth_nonce' => Nonce::getNonce('gaimport.auth', 1200), 'hasClientConfiguration' => $hasClientConfiguration, 'nonce' => $nonce, 'statuses' => $statuses, 'stopImportNonce' => $stopImportNonce, 'startImportNonce' => $startImportNonce, 'changeImportEndDateNonce' => $changeImportEndDateNonce, 'resumeImportNonce' => $resumeImportNonce, 'scheduleReImportNonce' => $scheduleReImportNonce, 'maxEndDateDesc' => $maxEndDateDesc, 'importOptionsUA' => array('ua' => Piwik::translate('GoogleAnalyticsImporter_SelectImporterUATitle')), 'importOptionsGA4' => ['ga4' => Piwik::translate('GoogleAnalyticsImporter_SelectImporterGA4Title')], 'extraCustomDimensionsField' => ['field1' => ['key' => 'gaDimension', 'title' => Piwik::translate('GoogleAnalyticsImporter_GADimension'), 'uiControl' => 'text', 'availableValues' => null], 'field2' => ['key' => 'dimensionScope', 'title' => Piwik::translate('GoogleAnalyticsImporter_DimensionScope'), 'uiControl' => 'select', 'availableValues' => ['visit' => Piwik::translate('General_Visit'), 'action' => Piwik::translate('General_Action')]]], 'extraCustomDimensionsFieldGA4' => ['field1' => ['key' => 'ga4Dimension', 'title' => Piwik::translate('GoogleAnalyticsImporter_GA4Dimension'), 'uiControl' => 'text', 'availableValues' => null], 'field2' => ['key' => 'dimensionScope', 'title' => Piwik::translate('GoogleAnalyticsImporter_DimensionScope'), 'uiControl' => 'select', 'availableValues' => ['visit' => Piwik::translate('General_Visit'), 'action' => Piwik::translate('General_Action')]]], 'streamIdsFieldGA4' => ['field1' => ['key' => 'streamId', 'title' => Piwik::translate('GoogleAnalyticsImporter_StreamId'), 'uiControl' => 'text', 'availableValues' => null]], 'extensions' => self::getComponentExtensions(), 'configureConnectionProps' => $configureConnectionProps]);
    }
    public function forwardToAuth()
    {
        Piwik::checkUserHasSuperUserAccess();
        Nonce::checkNonce('gaimport.auth', Common::getRequestVar('auth_nonce'));
        /** @var Authorization $authorization */
        $authorization = StaticContainer::get(Authorization::class);
        /** @var \Google\Client $client */
        $client = $authorization->getConfiguredClient();
        $state = Nonce::getNonce(self::OAUTH_STATE_NONCE_NAME, 900);
        $client->setState($state);
        $client->setPrompt('consent');
        Url::redirectToUrl($client->createAuthUrl());
    }
    public function deleteClientCredentials()
    {
        Piwik::checkUserHasSuperUserAccess();
        Nonce::checkNonce('GoogleAnalyticsImporter.deleteGoogleClientConfig', Common::getRequestVar('config_nonce'));
        /** @var Authorization $authorization */
        $authorization = StaticContainer::get(Authorization::class);
        $authorization->deleteClientConfiguration();
        // Redirect to index so that will be the URL and not the delete URL
        Url::redirectToUrl(Url::getCurrentUrlWithoutQueryString() . Url::getCurrentQueryStringWithParametersModified(['action' => 'index', 'code' => null, 'scope' => null, 'state' => null]));
    }
    /**
     * Processes the response from google oauth service
     *
     * @return string
     * @throws \Exception
     */
    public function processAuthCode()
    {
        Piwik::checkUserHasSuperUserAccess();
        $error = Common::getRequestVar('error', '');
        $oauthCode = Common::getRequestVar('code', '');
        $state = Common::getRequestVar('state');
        if ($state && !empty($_SERVER['HTTP_REFERER']) && stripos($_SERVER['HTTP_REFERER'], 'https://accounts.google.') === 0) {
            //We need to update this, else it will fail for referer like https://accounts.google.co.in
            $_SERVER['HTTP_REFERER'] = 'https://accounts.google.com';
        }
        Nonce::checkNonce(self::OAUTH_STATE_NONCE_NAME, $state, 'google.com');
        if ($error) {
            return $this->index($error);
        }
        try {
            /** @var Authorization $authorization */
            $authorization = StaticContainer::get(Authorization::class);
            $client = $authorization->getConfiguredClient();
            $authorization->saveAccessToken($oauthCode, $client);
        } catch (\Exception $e) {
            return $this->index($this->getNotificationExceptionText($e));
        }
        // reload index action to prove everything is configured
        $this->redirectToIndex('GoogleAnalyticsImporter', 'index');
    }
    public function configureClient()
    {
        Piwik::checkUserHasSuperUserAccess();
        Nonce::checkNonce('GoogleAnalyticsImporter.googleClientConfig', Common::getRequestVar('config_nonce'));
        if (\Piwik\Plugins\GoogleAnalyticsImporter\GoogleAnalyticsImporter::isConnectAccountsPluginActivated() && GoogleConnect::isStrategyActive()) {
            GoogleConnect::disableMatomoCloudOverride();
        }
        /** @var Authorization $authorization */
        $authorization = StaticContainer::get(Authorization::class);
        $errorMessage = null;
        try {
            $config = Common::getRequestVar('client', '');
            $config = Common::unsanitizeInputValue($config);
            if (empty($config) && !empty($_FILES['clientfile'])) {
                if (!empty($_FILES['clientfile']['error'])) {
                    throw new \Exception('Client file upload failed: ' . $_FILES['clientfile']['error']);
                }
                $file = $_FILES['clientfile']['tmp_name'];
                if (!file_exists($file)) {
                    $logger = StaticContainer::get(LoggerInterface::class);
                    $logger->error('Client file upload failed: temporary file does not exist (path is {path})', ['path' => $file]);
                    throw new \Exception('Client file upload failed: temporary file does not exist');
                }
                $config = file_get_contents($_FILES['clientfile']['tmp_name']);
            }
            $authorization->validateConfig($config);
            $authorization->saveConfig($config);
        } catch (\Exception $ex) {
            $errorMessage = $this->getNotificationExceptionText($ex);
            $errorMessage = substr($errorMessage, 0, 1024);
        }
        $modifiedParameters = ['action' => 'index', 'error' => $errorMessage];
        $hashParams = '';
        $isNoDataPage = Common::getRequestVar('isNoDataPage', '');
        if ($isNoDataPage) {
            $hashParams = '#?activeTab=googleanalyticsimporter';
            $modifiedParameters = ['module' => 'CoreHome', 'action' => 'index'];
        }
        Url::redirectToUrl(Url::getCurrentUrlWithoutQueryString() . Url::getCurrentQueryStringWithParametersModified($modifiedParameters) . $hashParams);
    }
    public function deleteImportStatus()
    {
        Piwik::checkUserHasSuperUserAccess();
        $this->checkTokenInUrl();
        Json::sendHeaderJSON();
        try {
            Nonce::checkNonce('GoogleAnalyticsImporter.stopImportNonce', Common::getRequestVar('nonce'));
            $idSite = Common::getRequestVar('idSite', null, 'int');
            /** @var ImportStatus $importStatus */
            $importStatus = StaticContainer::get(\Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus::class);
            $importStatus->deleteStatus($idSite);
            echo json_encode(['result' => 'ok']);
        } catch (\Exception $ex) {
            $this->logException($ex, __FUNCTION__);
            $notification = new Notification($this->getNotificationExceptionText($ex));
            $notification->type = Notification::TYPE_TRANSIENT;
            $notification->context = Notification::CONTEXT_ERROR;
            $notification->title = Piwik::translate('General_Error');
            Notification\Manager::notify('GoogleAnalyticsImporter_deleteImportStatus_failure', $notification);
        }
    }
    public function changeImportEndDate()
    {
        Piwik::checkUserHasSuperUserAccess();
        $this->checkTokenInUrl();
        Json::sendHeaderJSON();
        try {
            Nonce::checkNonce('GoogleAnalyticsImporter.changeImportEndDateNonce', Common::getRequestVar('nonce'));
            $idSite = Common::getRequestVar('idSite', null, 'int');
            $endDate = Common::getRequestVar('endDate', '', 'string');
            $inputEndDate = StaticContainer::get(EndDate::class);
            $endDate = $inputEndDate->limitMaxEndDateIfNeeded($endDate);
            /** @var ImportStatus $importStatus */
            $importStatus = StaticContainer::get(\Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus::class);
            $status = $importStatus->getImportStatus($idSite);
            $importStatus->setImportDateRange($idSite, empty($status['import_range_start']) ? null : Date::factory($status['import_range_start']), empty($endDate) ? null : Date::factory($endDate));
            echo json_encode(['result' => 'ok']);
        } catch (\Exception $ex) {
            $this->logException($ex, __FUNCTION__);
            $notification = new Notification($this->getNotificationExceptionText($ex));
            $notification->type = Notification::TYPE_TRANSIENT;
            $notification->context = Notification::CONTEXT_ERROR;
            $notification->title = Piwik::translate('General_Error');
            Notification\Manager::notify('GoogleAnalyticsImporter_changeImportEndDate_failure', $notification);
        }
    }
    public function startImport()
    {
        Piwik::checkUserHasSuperUserAccess();
        $this->checkTokenInUrl();
        Json::sendHeaderJSON();
        try {
            Nonce::checkNonce('GoogleAnalyticsImporter.startImportNonce', Common::getRequestVar('nonce'));
            $startDate = trim(Common::getRequestVar('startDate', ''));
            if (!empty($startDate)) {
                $startDate = Date::factory($startDate . ' 00:00:00');
            }
            $endDate = trim(Common::getRequestVar('endDate', ''));
            $inputEndDate = StaticContainer::get(EndDate::class);
            $endDate = $inputEndDate->limitMaxEndDateIfNeeded($endDate);
            if (!empty($endDate)) {
                $endDate = Date::factory($endDate)->getStartOfDay();
            }
            // set credentials in google client
            $googleAuth = StaticContainer::get(Authorization::class);
            $googleAuth->getConfiguredClient();
            /** @var Importer $importer */
            $importer = StaticContainer::get(\Piwik\Plugins\GoogleAnalyticsImporter\Importer::class);
            $propertyId = trim(Common::getRequestVar('propertyId'));
            $viewId = trim(Common::getRequestVar('viewId'));
            $accountId = trim(Common::getRequestVar('accountId', \false));
            $account = $accountId ?: ImportReports::guessAccountFromProperty($propertyId);
            $isMobileApp = Common::getRequestVar('isMobileApp', 0, 'int') == 1;
            $timezone = trim(Common::getRequestVar('timezone', '', 'string'));
            $extraCustomDimensions = Common::getRequestVar('extraCustomDimensions', [], $type = 'array');
            $isVerboseLoggingEnabled = Common::getRequestVar('isVerboseLoggingEnabled', 0, $type = 'int') == 1;
            $forceCustomDimensionSlotCheck = Common::getRequestVar('forceCustomDimensionSlotCheck', 1, $type = 'int') == 1;
            $idSite = $importer->makeSite($account, $propertyId, $viewId, $timezone, $isMobileApp ? Type::ID : \Piwik\Plugins\WebsiteMeasurable\Type::ID, $extraCustomDimensions, $forceCustomDimensionSlotCheck);
            try {
                if (empty($idSite)) {
                    throw new \Exception("Unable to import site entity.");
                    // sanity check
                }
                /** @var ImportStatus $importStatus */
                $importStatus = StaticContainer::get(\Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus::class);
                if (!empty($startDate) || !empty($endDate)) {
                    // we set the last imported date to one day before the start date
                    $importStatus->setImportDateRange($idSite, $startDate ?: null, $endDate ?: null);
                }
                if ($isVerboseLoggingEnabled) {
                    $importStatus->setIsVerboseLoggingEnabled($idSite, $isVerboseLoggingEnabled);
                }
                // start import now since the scheduled task may not run until tomorrow
                \Piwik\Plugins\GoogleAnalyticsImporter\Tasks::startImport($importStatus->getImportStatus($idSite));
            } catch (\Exception $ex) {
                $importStatus->erroredImport($idSite, $ex->getMessage());
                throw $ex;
            }
            echo json_encode(['result' => 'ok']);
        } catch (\Exception $ex) {
            $this->logException($ex, __FUNCTION__);
            $notification = new Notification($this->getNotificationExceptionText($ex));
            $notification->type = Notification::TYPE_TRANSIENT;
            $notification->context = Notification::CONTEXT_ERROR;
            $notification->title = Piwik::translate('General_Error');
            Notification\Manager::notify('GoogleAnalyticsImporter_startImport_failure', $notification);
        }
    }
    public function startImportGA4()
    {
        Piwik::checkUserHasSuperUserAccess();
        $this->checkTokenInUrl();
        Json::sendHeaderJSON();
        try {
            Nonce::checkNonce('GoogleAnalyticsImporter.startImportNonce', Common::getRequestVar('nonce'));
            $startDate = trim(Common::getRequestVar('startDate', ''));
            if (!empty($startDate)) {
                $startDate = Date::factory($startDate . ' 00:00:00');
            }
            $endDate = trim(Common::getRequestVar('endDate', ''));
            $inputEndDate = StaticContainer::get(EndDate::class);
            $endDate = $inputEndDate->limitMaxEndDateIfNeeded($endDate);
            if (!empty($endDate)) {
                $endDate = Date::factory($endDate)->getStartOfDay();
            }
            // set credentials in google client
            $googleAuth = StaticContainer::get(AuthorizationGA4::class);
            /** @var ImporterGA4 $importer */
            $importer = StaticContainer::get(\Piwik\Plugins\GoogleAnalyticsImporter\ImporterGA4::class);
            $importer->setGAClient($googleAuth->getClient());
            $importer->setGAAdminClient($googleAuth->getAdminClient());
            $propertyId = trim(Common::getRequestVar('propertyId'));
            ImportGA4Reports::validatePropertyID($propertyId);
            $isMobileApp = Common::getRequestVar('isMobileApp', 0, 'int') == 1;
            $timezone = trim(Common::getRequestVar('timezone', '', 'string'));
            $extraCustomDimensions = Common::getRequestVar('extraCustomDimensions', [], $type = 'array');
            $streamIds = $this->getStreamIdsFromRequest();
            $isVerboseLoggingEnabled = Common::getRequestVar('isVerboseLoggingEnabled', 0, $type = 'int') == 1;
            $forceCustomDimensionSlotCheck = Common::getRequestVar('forceCustomDimensionSlotCheck', 1, $type = 'int') == 1;
            $idSite = $importer->makeSite($propertyId, $timezone, $isMobileApp ? Type::ID : \Piwik\Plugins\WebsiteMeasurable\Type::ID, $extraCustomDimensions, $forceCustomDimensionSlotCheck, $streamIds);
            try {
                if (empty($idSite)) {
                    throw new \Exception("Unable to import site entity.");
                    // sanity check
                }
                /** @var ImportStatus $importStatus */
                $importStatus = StaticContainer::get(\Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus::class);
                if (!empty($startDate) || !empty($endDate)) {
                    // we set the last imported date to one day before the start date
                    $importStatus->setImportDateRange($idSite, $startDate ?: null, $endDate ?: null);
                }
                if ($isVerboseLoggingEnabled) {
                    $importStatus->setIsVerboseLoggingEnabled($idSite, $isVerboseLoggingEnabled);
                }
                // start import now since the scheduled task may not run until tomorrow
                \Piwik\Plugins\GoogleAnalyticsImporter\Tasks::startImportGA4($importStatus->getImportStatus($idSite));
            } catch (\Exception $ex) {
                $importStatus->erroredImport($idSite, $ex->getMessage());
                throw $ex;
            }
            echo json_encode(['result' => 'ok']);
        } catch (\Exception $ex) {
            $this->logException($ex, __FUNCTION__);
            $notification = new Notification($this->getNotificationExceptionText($ex));
            $notification->type = Notification::TYPE_TRANSIENT;
            $notification->context = Notification::CONTEXT_ERROR;
            $notification->title = Piwik::translate('General_Error');
            Notification\Manager::notify('GoogleAnalyticsImporter_startImport_failure', $notification);
        }
    }
    public function resumeImport()
    {
        Piwik::checkUserHasSuperUserAccess();
        $this->checkTokenInUrl();
        Json::sendHeaderJSON();
        try {
            Nonce::checkNonce('GoogleAnalyticsImporter.resumeImportNonce', Common::getRequestVar('nonce'));
            $idSite = Common::getRequestVar('idSite', null, 'int');
            $isGA4 = Common::getRequestVar('isGA4', 0, 'int') == 1;
            new Site($idSite);
            /** @var ImportStatus $importStatus */
            $importStatus = StaticContainer::get(\Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus::class);
            $status = $importStatus->getImportStatus($idSite);
            if ($status['status'] == \Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus::STATUS_FINISHED) {
                throw new \Exception("This import cannot be resumed since it is finished.");
            }
            if ($status['status'] != \Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus::STATUS_FUTURE_DATE_IMPORT_PENDING) {
                // If we do not check this, future import dates will not be processed, and it will be marked as finished
                $importStatus->resumeImport($idSite);
            }
            if ($isGA4) {
                \Piwik\Plugins\GoogleAnalyticsImporter\Tasks::startImportGA4($status);
            } else {
                \Piwik\Plugins\GoogleAnalyticsImporter\Tasks::startImport($status);
            }
            echo json_encode(['result' => 'ok']);
        } catch (\Exception $ex) {
            $this->logException($ex, __FUNCTION__);
            $notification = new Notification($this->getNotificationExceptionText($ex));
            $notification->type = Notification::TYPE_TRANSIENT;
            $notification->context = Notification::CONTEXT_ERROR;
            $notification->title = Piwik::translate('General_Error');
            Notification\Manager::notify('GoogleAnalyticsImporter_resumeImport_failure', $notification);
        }
    }
    public function scheduleReImport()
    {
        Piwik::checkUserHasSuperUserAccess();
        $this->checkTokenInUrl();
        Json::sendHeaderJSON();
        try {
            Nonce::checkNonce('GoogleAnalyticsImporter.scheduleReImport', Common::getRequestVar('nonce'));
            $idSite = Common::getRequestVar('idSite', null, 'int');
            new Site($idSite);
            $isGA4 = Common::getRequestVar('isGA4', 0, 'int') == 1;
            $startDate = Common::getRequestVar('startDate', null, 'string');
            $startDate = Date::factory($startDate);
            $endDate = Common::getRequestVar('endDate', null, 'string');
            $inputEndDate = StaticContainer::get(EndDate::class);
            $endDate = $inputEndDate->limitMaxEndDateIfNeeded($endDate);
            $endDate = Date::factory($endDate);
            /** @var ImportStatus $importStatus */
            $importStatus = StaticContainer::get(\Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus::class);
            $status = $importStatus->getImportStatus($idSite);
            //For UI test to work properly after an error
            if ($isGA4 && defined('PIWIK_TEST_MODE') && $status['import_range_end'] === '2019-07-02') {
                $importStatus->setImportDateRange($idSite, $startDate, $endDate);
            }
            $importStatus->reImportDateRange($idSite, $startDate, $endDate);
            $importStatus->resumeImport($idSite);
            // start import now since the scheduled task may not run until tomorrow
            if ($isGA4) {
                \Piwik\Plugins\GoogleAnalyticsImporter\Tasks::startImportGA4($importStatus->getImportStatus($idSite));
            } else {
                \Piwik\Plugins\GoogleAnalyticsImporter\Tasks::startImport($importStatus->getImportStatus($idSite));
            }
            echo json_encode(['result' => 'ok']);
        } catch (\Exception $ex) {
            $this->logException($ex, __FUNCTION__);
            $notification = new Notification($this->getNotificationExceptionText($ex));
            $notification->type = Notification::TYPE_TRANSIENT;
            $notification->context = Notification::CONTEXT_ERROR;
            $notification->title = Piwik::translate('General_Error');
            Notification\Manager::notify('GoogleAnalyticsImporter_rescheduleImport_failure', $notification);
        }
    }
    private function logException(\Throwable $ex, $functionName)
    {
        StaticContainer::get(LoggerInterface::class)->debug('Encountered exception in GoogleAnalyticsImporter.{function} controller method: {exception}', ['exception' => $ex, 'function' => $functionName]);
    }
    private function getNotificationExceptionText(\Exception $e)
    {
        $message = $e->getMessage();
        $messageContent = @json_decode($message, \true);
        if (\Piwik_ShouldPrintBackTraceWithMessage()) {
            $message .= "\n" . $e->getTraceAsString();
        } else {
            if (isset($messageContent['error']['message'])) {
                $message = $messageContent['error']['message'];
            }
        }
        return $message;
    }
    public function pendingImports()
    {
        $pendingImports = \Piwik\Plugins\GoogleAnalyticsImporter\GoogleAnalyticsImporter::canDisplayImportPendingNotice();
        return json_encode($pendingImports);
    }
    /**
     * Helps to determine whether to show notification to configure GA import
     * To show a notification, User should be admin and some data should have tracked and no import has configured as well as GA has been detected on the site
     * @return Json
     */
    public function displayConfigureImportNotification()
    {
        $showNotification = \false;
        $settingsUrl = '';
        $currentIdSite = Common::getRequestVar('idSite', -1);
        if (Piwik::hasUserSuperUserAccess() && SitesManager::hasTrackedAnyTraffic($currentIdSite) && class_exists(\Piwik\SiteContentDetector::class)) {
            $importStatus = new \Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus();
            try {
                $status = $importStatus->getAllImportStatuses();
            } catch (\Exception $exception) {
                $status = [];
                //No Import is configured
            }
            if (empty($status)) {
                $siteContentDetector = new \Piwik\SiteContentDetector();
                $siteContentDetector->detectContent([GoogleAnalytics3::getId(), GoogleAnalytics4::getId()], $currentIdSite);
                if ($siteContentDetector->wasDetected(GoogleAnalytics3::getId()) || $siteContentDetector->wasDetected(GoogleAnalytics4::getId())) {
                    $showNotification = \true;
                    $settingsUrl = SettingsPiwik::getPiwikUrl() . 'index.php?' . Url::getQueryStringFromParameters(['idSite' => $currentIdSite, 'module' => 'GoogleAnalyticsImporter', 'action' => 'index']);
                }
            }
        }
        return json_encode(['showNotification' => $showNotification, 'configureURL' => $settingsUrl]);
    }
    /**
     * Get the map of component extensions to be passed into the Vue template. This allows other plugins to provide
     * content to display in the template. In this case this plugin will display one component, but that can be
     * overridden by the ConnectAccounts plugin to display a somewhat different component. This is doing something
     * similar to what we use {{ postEvent('MyPlugin.MyEventInATemplate) }} for in Twig templates.
     *
     * @return array Map of component extensions. Like [ [ 'plugin' => 'PluginName', 'component' => 'ComponentName' ] ]
     * See {@link https://developer.matomo.org/guides/in-depth-vue#allowing-plugins-to-add-content-to-your-vue-components the developer documentation} for more information.
     */
    public static function getComponentExtensions($isNoDataPage = \false) : array
    {
        $componentExtensions = [];
        Piwik::postEvent('GoogleAnalyticsImporter.getGoogleConfigComponentExtensions', [&$componentExtensions, $isNoDataPage]);
        return $componentExtensions;
    }
    private function getStreamIdsFromRequest()
    {
        $request = \Piwik\Request::fromRequest();
        $streamIds = $request->getArrayParameter('streamIds', []);
        $ids = [];
        if (!empty($streamIds)) {
            foreach ($streamIds as $value) {
                if (!empty($value['streamId'])) {
                    $ids[] = $value['streamId'];
                }
            }
        }
        return $ids;
    }
}
