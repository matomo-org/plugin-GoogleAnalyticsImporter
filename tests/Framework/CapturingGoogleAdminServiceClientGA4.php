<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Framework;

use Matomo\Dependencies\GoogleAnalyticsImporter\Google\ApiCore\GapicClientTrait;
class CapturingGoogleAdminServiceClientGA4 extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\AnalyticsAdminServiceClient
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
        $rc = new \ReflectionClass(\Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\AnalyticsAdminServiceClient::class);
        $parentDir = dirname($rc->getFileName());
        return ['serviceName' => parent::SERVICE_NAME, 'apiEndpoint' => parent::SERVICE_ADDRESS . ':' . parent::DEFAULT_SERVICE_PORT, 'clientConfig' => $parentDir . '/resources/analytics_admin_service_client_config.json', 'descriptorsConfigPath' => $parentDir . '/resources/analytics_admin_service_descriptor_config.php', 'gcpApiConfigPath' => $parentDir . '/resources/analytics_admin_service_grpc_config.json', 'credentialsConfig' => ['defaultScopes' => parent::$serviceScopes], 'transportConfig' => ['rest' => ['restClientConfigPath' => $parentDir . '/resources/analytics_admin_service_rest_client_config.php']]];
    }
}
