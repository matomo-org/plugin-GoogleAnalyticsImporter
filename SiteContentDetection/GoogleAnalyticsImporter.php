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
    public static function getName() : string
    {
        return Piwik::translate('GoogleAnalyticsImporter_AdminMenuTitle');
    }
    public static function getIcon() : string
    {
        return './plugins/GoogleAnalyticsImporter/images/ga-icon.svg';
    }
    public static function getContentType() : int
    {
        return self::TYPE_OTHER;
    }
    public static function getPriority() : int
    {
        return 25;
    }
    public function isDetected(?string $data = null, ?array $headers = null) : bool
    {
        return \false;
    }
    public function isRecommended(SiteContentDetector $detector) : bool
    {
        return $detector->wasDetected(GoogleAnalytics3::getId()) || $detector->wasDetected(GoogleAnalytics4::getId());
    }
    public function getRecommendationDetails(SiteContentDetector $detector) : array
    {
        return ['title' => Piwik::translate('GoogleAnalyticsImporter_RecommendationTitle'), 'text' => Piwik::translate('GoogleAnalyticsImporter_RecommendationText'), 'button' => Piwik::translate('GoogleAnalyticsImporter_RecommendationButton')];
    }
    public function renderInstructionsTab(SiteContentDetector $detector) : string
    {
        // Only show the tab if the current user is super user
        if (!Piwik::hasUserSuperUserAccess()) {
            return '';
        }

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
        $view->extensions = Controller::getComponentExtensions(\true);
        $view->hasClientConfiguration = $authorization->hasClientConfiguration();
        $view->isConfigured = $authorization->hasAccessToken();
        return $view->render();
    }
}
