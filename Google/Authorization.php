<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\Google;


use Piwik\Container\StaticContainer;
use Piwik\Option;
use Piwik\Piwik;
use Piwik\Url;

class Authorization
{
    const ACCESS_TOKEN_OPTION_NAME = 'GoogleAnalyticsImporter.oauthAccessToken';
    const CLIENT_CONFIG_OPTION_NAME = 'GoogleAnalyticsImporter.clientConfiguration';

    public function hasAccessToken()
    {
        $value = $this->getAccessToken();
        return !empty($value);
    }

    private function getAccessToken()
    {
        $value = Option::get(self::ACCESS_TOKEN_OPTION_NAME);
        return $value;
    }

    public function hasClientConfiguration()
    {
        $value = $this->getClientConfiguration();
        return !empty($value);
    }

    public function getClientConfiguration()
    {
        $value = Option::get(self::CLIENT_CONFIG_OPTION_NAME);
        $value = @json_decode($value, true);
        return $value;
    }

    public function validateConfig($config)
    {
        $value = @json_encode($config, true);
        if (empty($value)) {
            throw new \Exception(Piwik::translate('GoogleAnalyticsImporter_InvalidClientJson'));
        }
    }

    public function saveConfig($config)
    {
        Option::set(self::CLIENT_CONFIG_OPTION_NAME, $config);
    }

    public function saveAccessToken($oauthCode, \Google_Client $client)
    {
        $accessToken = $client->fetchAccessTokenWithAuthCode($oauthCode);

        $tokenInfo = $this->getTokenInfo($accessToken, $client);

        // if token is not valid for offline access redirect back to get it granted
        if ($tokenInfo->access_type != 'offline') {
            Url::redirectToUrl($client->createAuthUrl());
        }

        $accessTokenStr = $accessToken;
        if (!is_string($accessToken)) {
            $accessTokenStr = json_encode($accessToken);
        }

        Option::set(self::ACCESS_TOKEN_OPTION_NAME, $accessTokenStr);
    }

    /**
     * Returns information for the given access token
     *
     * @param array $accessToken
     * @return \Google_Service_Oauth2_Tokeninfo
     * @throws \Exception
     */
    protected function getTokenInfo($accessToken, $client)
    {
        $service = new \Google_Service_Oauth2($client);
        return $service->tokeninfo(['access_token' => $accessToken['access_token']]);
    }

    public function configureClient(\Google_Client $client)
    {
        $clientConfig = $this->getClientConfiguration();

        try {
            @$client->setAuthConfig($clientConfig);
        } catch (\Exception $e) {
            throw new \Exception(Piwik::translate('GoogleAnalyticsImporter_MissingClientConfiguration'));
        }

        // no client config available
        if (!$client->getClientId() || !$client->getClientSecret()) {
            throw new \Exception(Piwik::translate('GoogleAnalyticsImporter_MissingClientConfiguration'));
        }

        $accessToken = $this->getAccessToken();
        if (!empty($accessToken)) {
            $client->setAccessToken($accessToken);
        }
    }

    public function deleteClientConfiguration()
    {
        Option::delete(self::ACCESS_TOKEN_OPTION_NAME);
        Option::delete(self::CLIENT_CONFIG_OPTION_NAME);
    }

    public function getConfiguredClient()
    {
        $client = StaticContainer::get('GoogleAnalyticsImporter.googleClient');
        $this->configureClient($client);
        return $client;
    }

    protected function getUserInfoByAccessToken(\Google_Client $client)
    {
        $service = new \Google_Service_Oauth2($client);
        return $service->userinfo->get();
    }
}