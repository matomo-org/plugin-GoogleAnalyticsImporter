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
use Piwik\Nonce;
use Piwik\Notification;
use Piwik\Piwik;
use Piwik\Plugins\GoogleAnalyticsImporter\Commands\ImportReports;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\Authorization;
use Piwik\Url;
use Psr\Log\LoggerInterface;

class Controller extends \Piwik\Plugin\ControllerAdmin
{
    public function index($errorMessage = false)
    {
        Piwik::checkUserHasSuperUserAccess();

        $errorMessage = $errorMessage ?: Common::getRequestVar('error', '');
        if (!empty($errorMessage)) {
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

            $nonce = Nonce::getNonce('GoogleAnalyticsImporter.deleteGoogleClientConfig');
        } else {
            $nonce = Nonce::getNonce('GoogleAnalyticsImporter.googleClientConfig');
        }

        $importStatus = StaticContainer::get(ImportStatus::class);
        $statuses = $importStatus->getAllImportStatuses();

        $stopImportNonce = Nonce::getNonce('GoogleAnalyticsImporter.stopImportNonce');
        $startImportNonce = Nonce::getNonce('GoogleAnalyticsImporter.startImportNonce');

        return $this->renderTemplate('index', [
            'isConfigured' => $authorization->hasAccessToken(),
            'authUrl' => $authUrl,
            'hasClientConfiguration' => $hasClientConfiguration,
            'nonce' => $nonce,
            'statuses' => $statuses,
            'stopImportNonce' => $stopImportNonce,
            'startImportNonce' => $startImportNonce,
        ]);
    }

    public function deleteClientCredentials()
    {
        Piwik::checkUserHasSuperUserAccess();

        Nonce::checkNonce('GoogleAnalyticsImporter.deleteGoogleClientConfig', Common::getRequestVar('config_nonce'));

        /** @var Authorization $authorization */
        $authorization = StaticContainer::get(Authorization::class);

        $authorization->deleteClientConfiguration();

        return $this->index();
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

        $error     = Common::getRequestVar('error', '');
        $oauthCode = Common::getRequestVar('code', '');

        if ($error) {
            return $this->index($error);
        }

        try {
            /** @var Authorization $authorization */
            $authorization = StaticContainer::get(Authorization::class);

            $client = $authorization->getConfiguredClient();
            $authorization->saveAccessToken($oauthCode, $client);
        } catch (\Exception $e) {
            return $this->index($e->getMessage());
        }

        // reload index action to prove everything is configured
        Url::redirectToUrl(Url::getCurrentUrlWithoutQueryString() . Url::getCurrentQueryStringWithParametersModified([
            'action' => 'index',
            'code' => '',
        ]));
    }

    public function configureClient()
    {
        Piwik::checkUserHasSuperUserAccess();

        Nonce::checkNonce('GoogleAnalyticsImporter.googleClientConfig', Common::getRequestVar('config_nonce'));

        /** @var Authorization $authorization */
        $authorization = StaticContainer::get(Authorization::class);

        $errorMessage = null;

        try {
            $config = Common::getRequestVar('client', '');

            if (empty($config) && !empty($_FILES['clientfile'])) {

                if (!empty($_FILES['clientfile']['error'])) {
                    throw new \Exception('Client file upload failed: ' . $_FILES['clientfile']['error']);
                }

                $file = $_FILES['clientfile']['tmp_name'];
                if (!file_exists($file)) {
                    $logger = StaticContainer::get(LoggerInterface::class);
                    $logger->error('Client file upload failed: temporary file does not exist (path is {path})', [
                        'path' => $file,
                    ]);

                    throw new \Exception('Client file upload failed: temporary file does not exist');
                }

                $config = file_get_contents($_FILES['clientfile']['tmp_name']);
            }

            $authorization->validateConfig($config);
            $authorization->saveConfig($config);
        } catch (\Exception $ex) {
            $errorMessage = $ex->getMessage();
        }

        Url::redirectToUrl(Url::getCurrentUrlWithoutQueryString() . Url::getCurrentQueryStringWithParametersModified([
            'action' => 'index',
            'error' => $errorMessage,
        ]));
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
            $importStatus = StaticContainer::get(ImportStatus::class);
            $importStatus->deleteStatus($idSite);

            echo json_encode(['result' => 'ok']);
        } catch (\Exception $ex) {
            $notification = new Notification($ex->getMessage());
            $notification->type = Notification::TYPE_TRANSIENT;
            $notification->context = Notification::CONTEXT_ERROR;
            $notification->title = Piwik::translate('General_Error');
            $notification->hasNoClear();
            Notification\Manager::notify('GoogleAnalyticsImporter_deleteImportStatus_failure', $notification);
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
            if (!empty($endDate)) {
                $endDate = Date::factory($endDate . ' 00:00:00');
            }

            // set credentials in google client
            $googleAuth = StaticContainer::get(Authorization::class);
            $googleAuth->getConfiguredClient();

            /** @var Importer $importer */
            $importer = StaticContainer::get(Importer::class);

            $propertyId = Common::getRequestVar('propertyId');
            $viewId = Common::getRequestVar('viewId');
            $accountId = Common::getRequestVar('accountId', false);
            $account = $accountId ?: ImportReports::guessAccountFromProperty($propertyId);
            $timezone = trim(Common::getRequestVar('timezone', '', 'string'));

            $idSite = $importer->makeSite($account, $propertyId, $viewId, $timezone);
            try {

                if (empty($idSite)) {
                    throw new \Exception("Unable to import site entity."); // sanity check
                }

                if (!empty($startDate)
                    || !empty($endDate)
                ) {
                    /** @var ImportStatus $importStatus */
                    $importStatus = StaticContainer::get(ImportStatus::class);

                    // we set the last imported date to one day before the start date
                    $importStatus->setImportDateRange($idSite, $startDate ?: null, $endDate ?: null);
                }

                // start import now since the scheduled task may not run until tomorrow
                Tasks::startImport($idSite);
            } catch (\Exception $ex) {
                $importStatus->erroredImport($idSite, $ex->getMessage());

                throw $ex;
            }

            echo json_encode([ 'result' => 'ok' ]);
        } catch (\Exception $ex) {
            $notification = new Notification($ex->getMessage());
            $notification->type = Notification::TYPE_TRANSIENT;
            $notification->context = Notification::CONTEXT_ERROR;
            $notification->title = Piwik::translate('General_Error');
            $notification->hasNoClear();
            Notification\Manager::notify('GoogleAnalyticsImporter_startImport_failure', $notification);
        }
    }
}
