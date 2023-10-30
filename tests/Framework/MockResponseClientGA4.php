<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Framework;

use Google\ApiCore\GapicClientTrait;

require_once PIWIK_INCLUDE_PATH . '/plugins/GoogleAnalyticsImporter/vendor/autoload.php';

class MockResponseClientGA4 extends \Google\Analytics\Data\V1beta\BetaAnalyticsDataClient
{
    public static $isForSystemTest = false;
    private $mockResponses = [];

    use GapicClientTrait;

    public function __construct(array $options = [])
    {
        $defaultOptions = $this->getDefaultOptions();
        $clientOptions = $this->buildClientOptions(array_merge($defaultOptions, $options));
        $options['transport'] = MockRestTransport::build($clientOptions['apiEndpoint'], $clientOptions['transportConfig']['rest']['restClientConfigPath'], $clientOptions['transportConfig']['rest']);
        parent::__construct($options);
    }

    public function authenticate($code)
    {
        return 'testtoken';
    }

    private function getDefaultOptions()
    {
        $rc = new \ReflectionClass(\Google\Analytics\Data\V1beta\BetaAnalyticsDataClient::class);
        $parentDir = dirname($rc->getFileName());
        //Since parent::getClientDefaults is private we only add required params here
        //In future if getClientDefaults() is updated we need to update here too
        return [
            'serviceName' => parent::SERVICE_NAME,
            'apiEndpoint' =>
                parent::SERVICE_ADDRESS . ':' . parent::DEFAULT_SERVICE_PORT,
            'clientConfig' =>
                $parentDir .
                '/resources/beta_analytics_data_client_config.json',
            'descriptorsConfigPath' =>
                $parentDir .
                '/resources/beta_analytics_data_descriptor_config.php',
            'gcpApiConfigPath' =>
                $parentDir . '/resources/beta_analytics_data_grpc_config.json',
            'credentialsConfig' => [
                'defaultScopes' => parent::$serviceScopes,
            ],
            'transportConfig' => [
                'rest' => [
                    'restClientConfigPath' =>
                        $parentDir .
                        '/resources/beta_analytics_data_rest_client_config.php',
                ],
            ],
        ];
    }
}