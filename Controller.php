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
use Piwik\Plugins\MobileAppMeasurable\Type;
use Piwik\SettingsServer;
use Piwik\Site;
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
        $statuses = $importStatus->getAllImportStatuses($checkKilledStatus = true);

        $stopImportNonce = Nonce::getNonce('GoogleAnalyticsImporter.stopImportNonce');
        $startImportNonce = Nonce::getNonce('GoogleAnalyticsImporter.startImportNonce');
        $changeImportEndDateNonce = Nonce::getNonce('GoogleAnalyticsImporter.changeImportEndDateNonce');
        $resumeImportNonce = Nonce::getNonce('GoogleAnalyticsImporter.resumeImportNonce');
        $scheduleReImportNonce = Nonce::getNonce('GoogleAnalyticsImporter.scheduleReImport');

        return $this->renderTemplate('index', [
            'isConfigured' => $authorization->hasAccessToken(),
            'authUrl' => $authUrl,
            'hasClientConfiguration' => $hasClientConfiguration,
            'nonce' => $nonce,
            'statuses' => $statuses,
            'stopImportNonce' => $stopImportNonce,
            'startImportNonce' => $startImportNonce,
            'changeImportEndDateNonce' => $changeImportEndDateNonce,
            'resumeImportNonce' => $resumeImportNonce,
            'scheduleReImportNonce' => $scheduleReImportNonce,
            'extraCustomDimensionsField' => [
                'field1' => [
                    'key' => 'gaDimension',
                    'title' => Piwik::translate('GoogleAnalyticsImporter_GADimension'),
                    'uiControl' => 'text',
                    'availableValues' => null,
                ],
                'field2' => [
                    'key' => 'dimensionScope',
                    'title' => Piwik::translate('GoogleAnalyticsImporter_DimensionScope'),
                    'uiControl' => 'select',
                    'availableValues' => [
                        'visit' => Piwik::translate('General_Visit'),
                        'action' => Piwik::translate('General_Action'),
                    ],
                ],
            ],
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

	private function setFixedEndDateIfWordPress($endDate)
	{
		// if Matomo for WordPress is used, then the data will be imported into the same site as the data is also being
		// tracked into by the sounds. So we need to make sure the import ends before Matomo for WordPress was installed
		// otherwise it would potentially always overwrite already aggregated report data
		if (method_exists(SettingsServer::class, 'isMatomoForWordPress')
		    && SettingsServer::isMatomoForWordPress()
		    && defined('\WpMatomo\Installer::OPTION_NAME_INSTALL_DATE')) {

			$installDate = get_option(\WpMatomo\Installer::OPTION_NAME_INSTALL_DATE);

			if (empty($installDate)) {
				// matomo for WordPress was installed before this option was set
				// we have to make sure there will be an end date otherwise it will always overwrite data
				// we assume Matomo for WordPress was installed two days ago
				$installDate = Date::today()->subDay(2);
			} else {
				// import up to 1 day before original install
				$installDate = Date::factory($installDate)->subDay(1);
			}

			if (!$endDate || Date::factory($endDate)->isLater($installDate)) {
				$endDate = $installDate->toString();
			}
        }

		return $endDate;
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
        $this->redirectToIndex('GoogleAnalyticsImporter', 'index');
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

    public function changeImportEndDate()
    {
        Piwik::checkUserHasSuperUserAccess();
        $this->checkTokenInUrl();

        Json::sendHeaderJSON();

        try {
            Nonce::checkNonce('GoogleAnalyticsImporter.changeImportEndDateNonce', Common::getRequestVar('nonce'));

            $idSite = Common::getRequestVar('idSite', null, 'int');
            $endDate = Common::getRequestVar('endDate', '', 'string');

            $endDate = $this->setFixedEndDateIfWordPress($endDate);

            /** @var ImportStatus $importStatus */
            $importStatus = StaticContainer::get(ImportStatus::class);
            $status = $importStatus->getImportStatus($idSite);

            $importStatus->setImportDateRange($idSite,
                empty($status['import_range_start']) ? null : Date::factory($status['import_range_start']),
                empty($endDate) ? null : Date::factory($endDate));

            echo json_encode(['result' => 'ok']);
        } catch (\Exception $ex) {
            $notification = new Notification($ex->getMessage());
            $notification->type = Notification::TYPE_TRANSIENT;
            $notification->context = Notification::CONTEXT_ERROR;
            $notification->title = Piwik::translate('General_Error');
            $notification->hasNoClear();
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
	        $endDate = $this->setFixedEndDateIfWordPress($endDate);
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
            $isMobileApp = Common::getRequestVar('isMobileApp', 0, 'int') == 1;
            $timezone = trim(Common::getRequestVar('timezone', '', 'string'));
            $extraCustomDimensions = Common::getRequestVar('extraCustomDimensions', [], $type = 'array');
            $isVerboseLoggingEnabled = Common::getRequestVar('isVerboseLoggingEnabled', 0, $type = 'int') == 1;

            $idSite = $importer->makeSite($account, $propertyId, $viewId, $timezone, $isMobileApp ? Type::ID : \Piwik\Plugins\WebsiteMeasurable\Type::ID, $extraCustomDimensions);

            try {
                if (empty($idSite)) {
                    throw new \Exception("Unable to import site entity."); // sanity check
                }

                /** @var ImportStatus $importStatus */
                $importStatus = StaticContainer::get(ImportStatus::class);

                if (!empty($startDate)
                    || !empty($endDate)
                ) {
                    // we set the last imported date to one day before the start date
                    $importStatus->setImportDateRange($idSite, $startDate ?: null, $endDate ?: null);
                }

                if ($isVerboseLoggingEnabled) {
                    $importStatus->setIsVerboseLoggingEnabled($idSite, $isVerboseLoggingEnabled);
                }

                // start import now since the scheduled task may not run until tomorrow
                Tasks::startImport($importStatus->getImportStatus($idSite));
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

    public function resumeImport()
    {
        Piwik::checkUserHasSuperUserAccess();
        $this->checkTokenInUrl();

        Json::sendHeaderJSON();

        try {
            Nonce::checkNonce('GoogleAnalyticsImporter.resumeImportNonce', Common::getRequestVar('nonce'));

            $idSite = Common::getRequestVar('idSite', null, 'int');
            new Site($idSite);

            /** @var ImportStatus $importStatus */
            $importStatus = StaticContainer::get(ImportStatus::class);
            $status = $importStatus->getImportStatus($idSite);
            if ($status['status'] == ImportStatus::STATUS_FINISHED) {
                throw new \Exception("This import cannot be resumed since it is finished.");
            }

            $importStatus->resumeImport($idSite);

            Tasks::startImport($status);

            echo json_encode([ 'result' => 'ok' ]);
        } catch (\Exception $ex) {
            $notification = new Notification($ex->getMessage());
            $notification->type = Notification::TYPE_TRANSIENT;
            $notification->context = Notification::CONTEXT_ERROR;
            $notification->title = Piwik::translate('General_Error');
            $notification->hasNoClear();
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

            $startDate = Common::getRequestVar('startDate', null, 'string');
            $startDate = Date::factory($startDate);

            $endDate = Common::getRequestVar('endDate', null, 'string');
	        $endDate = $this->setFixedEndDateIfWordPress($endDate);

	        $endDate = Date::factory($endDate);

            /** @var ImportStatus $importStatus */
            $importStatus = StaticContainer::get(ImportStatus::class);
            $importStatus->reImportDateRange($idSite, $startDate, $endDate);
            $importStatus->resumeImport($idSite);

            // start import now since the scheduled task may not run until tomorrow
            Tasks::startImport($importStatus->getImportStatus($idSite));

            echo json_encode([ 'result' => 'ok' ]);
        } catch (\Exception $ex) {
            $notification = new Notification($ex->getMessage());
            $notification->type = Notification::TYPE_TRANSIENT;
            $notification->context = Notification::CONTEXT_ERROR;
            $notification->title = Piwik::translate('General_Error');
            $notification->hasNoClear();
            Notification\Manager::notify('GoogleAnalyticsImporter_rescheduleImport_failure', $notification);
        }
    }
}
