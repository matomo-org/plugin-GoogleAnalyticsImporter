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

class AuthorizationGA4
{
    const CLIENT_CONFIG_OPTION_NAME = 'GoogleAnalyticsImporter.clientConfiguration';

    public function getClient()
    {
        $client = new BetaAnalyticsDataClient([
            'credentials' => $this->getClientConfiguration()
        ]);

        return $client;
    }


    public function getAdminClient()
    {
        $adminClient = new AnalyticsAdminServiceClient([
            'credentials' => $this->getClientConfiguration()
        ]);

        return $adminClient;
    }

    public function getClientConfiguration()
    {
        $value = Option::get(self::CLIENT_CONFIG_OPTION_NAME);
        $value = @json_decode($value, true);
        return $value;
    }

}