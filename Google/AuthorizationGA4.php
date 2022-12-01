<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\Google;

use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Admin\V1alpha\AnalyticsAdminServiceClient;
use Piwik\Container\StaticContainer;
use Piwik\Piwik;
use Piwik\Option;

class AuthorizationGA4
{
    const CLIENT_CONFIG_OPTION_NAME = 'GoogleAnalyticsImporter.clientConfiguration';
    const ACCESS_TOKEN_OPTION_NAME = 'GoogleAnalyticsImporter.oauthAccessToken';

    public function getClient()
    {
        $klass = StaticContainer::get('GoogleAnalyticsImporter.googleAnalyticsDataClientClass');
        $client = new $klass([
            'credentials' => \Google\ApiCore\CredentialsWrapper::build([
                'keyFile' => $this->getClientConfiguration()
            ])
        ]);

        return $client;
    }

    public function getAdminClient()
    {
        $klass = StaticContainer::get('GoogleAnalyticsImporter.googleAnalyticsAdminServiceClientClass');
        $adminClient = new $klass([
            'credentials' => \Google\ApiCore\CredentialsWrapper::build([
                'keyFile' => $this->getClientConfiguration()
            ])
        ]);

        return $adminClient;
    }

    public function getClientConfiguration()
    {
        $clientConfig = StaticContainer::get('GoogleAnalyticsGA4Importer.clientConfiguration');

        return $clientConfig;
    }
}
