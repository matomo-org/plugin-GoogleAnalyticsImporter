<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\Google;

use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\AnalyticsAdminServiceClient;
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
            'credentials' => \Matomo\Dependencies\GoogleAnalyticsImporter\Google\ApiCore\CredentialsWrapper::build([
                'keyFile' => $this->getClientConfiguration()
            ])
        ]);

        return $client;
    }


    public function getAdminClient()
    {
        $klass = StaticContainer::get('GoogleAnalyticsImporter.googleAnalyticsAdminServiceClientClass');
        $adminClient = new $klass([
            'credentials' => \Matomo\Dependencies\GoogleAnalyticsImporter\Google\ApiCore\CredentialsWrapper::build([
                'keyFile' => $this->getClientConfiguration()
            ])
        ]);

        return $adminClient;
    }

    public function getClientConfiguration()
    {
        $value = Option::get(self::CLIENT_CONFIG_OPTION_NAME);
        $value = @json_decode($value, true);

        if (empty($value['web']['client_id']) || empty($value['web']['client_secret'])) {
            throw new \Exception(Piwik::translate('GoogleAnalyticsImporter_MissingClientConfiguration'));
        }

        return [
            'type' => 'authorized_user',
            'client_id' => $value['web']['client_id'],
            'client_secret' => $value['web']['client_secret'],
            'refresh_token' => $this->getRefreshToken()
        ];
    }

    private function getAccessToken()
    {
        $value = Option::get(self::ACCESS_TOKEN_OPTION_NAME);

        return $value;
    }

    private function getRefreshToken()
    {
        $accessToken = @json_decode($this->getAccessToken(), true);
        if (empty($accessToken['refresh_token'])) {
            throw new \Exception(Piwik::translate('GoogleAnalyticsImporter_MissingClientConfiguration'));
        }

        return $accessToken['refresh_token'];
    }
}
