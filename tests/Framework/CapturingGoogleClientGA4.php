<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Framework;

use Matomo\Dependencies\GoogleAnalyticsImporter\Google\ApiCore\GapicClientTrait;
class CapturingGoogleClientGA4 extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\BetaAnalyticsDataClient
{
    use GapicClientTrait;
    public function __construct(array $options = [])
    {
        $defaultOptions = $this->getDefaultOptions();
        $clientOptions = $this->buildClientOptions(array_merge($defaultOptions, $options));
        $options['transport'] = \Piwik\Plugins\GoogleAnalyticsImporter\tests\Framework\CaptureRestTransport::build($clientOptions['apiEndpoint'], $clientOptions['transportConfig']['rest']['restClientConfigPath'], $clientOptions['transportConfig']['rest']);
        parent::__construct($options);
    }
    private function getDefaultOptions()
    {
        $rc = new \ReflectionClass(\Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\BetaAnalyticsDataClient::class);
        $parentDir = dirname($rc->getFileName());
        //Since parent::getClientDefaults is private we only add required params here
        //In future if getClientDefaults() is updated we need to update here too
        return ['serviceName' => parent::SERVICE_NAME, 'apiEndpoint' => parent::SERVICE_ADDRESS . ':' . parent::DEFAULT_SERVICE_PORT, 'clientConfig' => $parentDir . '/resources/beta_analytics_data_client_config.json', 'descriptorsConfigPath' => $parentDir . '/resources/beta_analytics_data_descriptor_config.php', 'gcpApiConfigPath' => $parentDir . '/resources/beta_analytics_data_grpc_config.json', 'credentialsConfig' => ['defaultScopes' => parent::$serviceScopes], 'transportConfig' => ['rest' => ['restClientConfigPath' => $parentDir . '/resources/beta_analytics_data_rest_client_config.php']]];
    }
}
