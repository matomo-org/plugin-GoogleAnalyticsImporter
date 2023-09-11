<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\SiteContentDetection;

use Piwik\Container\StaticContainer;
use Piwik\Nonce;
use Piwik\Piwik;
use Piwik\Plugins\ConnectAccounts\Strategy\Google\GoogleConnect;
use Piwik\Plugins\GoogleAnalyticsImporter\Controller;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\Authorization;
use Piwik\Plugins\SitesManager\SiteContentDetection\GoogleAnalytics3;
use Piwik\Plugins\SitesManager\SiteContentDetection\GoogleAnalytics4;
use Piwik\SiteContentDetector;
use Piwik\View;

class GoogleAnalyticsImporter extends \Piwik\Plugins\SitesManager\SiteContentDetection\SiteContentDetectionAbstract
{
    public static function getName(): string
    {
        return 'Google Analytics Importer';
    }

    public static function getContentType(): string
    {
        return self::TYPE_OTHER;
    }

    public static function getPriority(): int
    {
        return 25;
    }

    public function isDetected(?string $data = null, ?array $headers = null): bool
    {
        return false;
    }

    public function shouldShowInstructionTab(SiteContentDetector $detector = null): bool
    {
        return Piwik::hasUserSuperUserAccess() && (
            $detector->wasDetected(GoogleAnalytics3::getId()) || $detector->wasDetected(GoogleAnalytics4::getId())
        );
    }

    public function shouldHighlightTabIfShown(): bool
    {
        return true;
    }

    public function renderInstructionsTab(SiteContentDetector $detector): string
    {
        $isConnectAccountsPluginActivated = \Piwik\Plugins\GoogleAnalyticsImporter\GoogleAnalyticsImporter::isConnectAccountsPluginActivated();
        /** @var Authorization $authorization */
        $authorization = StaticContainer::get(Authorization::class);
        $nonce = Nonce::getNonce('GoogleAnalyticsImporter.googleClientConfig', 1200);
        $view = new View("@GoogleAnalyticsImporter/gaImportNoData");
        $view->nonce = $nonce;
        $view->auth_nonce = Nonce::getNonce('gaimport.auth', 1200);
        $view->isConnectAccountsActivated = $isConnectAccountsPluginActivated;
        $view->strategy = $isConnectAccountsPluginActivated && GoogleConnect::isStrategyActive() ? GoogleConnect::getStrategyName() : 'CUSTOM';
        $view->isGA3 = $detector->wasDetected(GoogleAnalytics3::getId());
        $view->configureConnectionProps = \Piwik\Plugins\GoogleAnalyticsImporter\GoogleAnalyticsImporter::getConfigureConnectProps($nonce);
        $view->extensions = Controller::getComponentExtensions(true);
        $view->hasClientConfiguration = $authorization->hasClientConfiguration();
        $view->isConfigured = $authorization->hasAccessToken();
        $view->isNoDataPage = true;
        return $view->render();
    }

}
