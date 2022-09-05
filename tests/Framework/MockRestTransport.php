<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Framework;

use Google\ApiCore\Call;
use Google\ApiCore\RequestBuilder;
use Google\ApiCore\Transport\RestTransport;
use Google\ApiCore\Transport\HttpUnaryTransportTrait;
use Google\ApiCore\ValidationException;
use Piwik\Common;
use Piwik\Option;

class MockRestTransport extends RestTransport
{
    use HttpUnaryTransportTrait;

    /**
     * @var string
     */
    private $capturedDataFile;
    private $mockResponses = [];

    public function __construct(
        RequestBuilder $requestBuilder,
        callable       $httpHandler
    )
    {
        MockResponseBuilderGA4::populateMockResponse();
        parent::__construct($requestBuilder, $httpHandler);
    }

    public static function build($apiEndpoint, $restConfigPath, array $config = [])
    {
        $config += [
            'httpHandler' => null,
            'clientCertSource' => null,
        ];
        list($baseUri, $port) = self::normalizeServiceAddress($apiEndpoint);
        $requestBuilder = new RequestBuilder("$baseUri:$port", $restConfigPath);
        $httpHandler = $config['httpHandler'] ?: self::buildHttpHandlerAsync();
        $transport = new MockRestTransport($requestBuilder, $httpHandler);
        if ($config['clientCertSource']) {
            $transport->configureMtlsChannel($config['clientCertSource']);
        }
        return $transport;
    }

    public function startUnaryCall(Call $call, array $options)
    {
        // for the UI test induce a failure on a specific day
//        if (!self::$isForSystemTest) {
//            if (!$this->hasErroredOnce()
//                && Common::isPhpCliMode()
//                && strpos($request->getBody()->getContents(), '2019-06-28') === false
//            ) {
//                sleep(10); // wait 10s to make sure UI test reloads w/ 'ongoing' status
//                $this->setErroredOnce();
//                throw new \Exception("forced error for test");
//            }
//        }

        if (isset($options['headers']['x-goog-api-client'])) {
            $options['headers']['x-goog-api-client'] = [];
        }

        $requestParts = [
            $call->getMethod(),
            utf8_encode($call->getMessage()->serializeToJsonString()),
            $call->getCallType(),
            $call->getDecodeType(),
            $call->getDescriptor(),
            $options
        ];

        $key = json_encode(json_decode(json_encode($requestParts), true)); // need to do double json encode/decode to convert objects {} -> to array [], else the md5 will mismatch
        $key = $this->replaceEnvVars($key);
        $key = md5($key);
        if (empty(MockResponseBuilderGA4::$responses[$key])) {
            throw new \Exception("Could not find mock response for request: " . json_encode($requestParts));
        }

        return MockResponseBuilderGA4::$responses[$key];
    }

    private static function normalizeServiceAddress($apiEndpoint)
    {
        $components = explode(':', $apiEndpoint);
        if (count($components) == 2) {
            // Port is included in service address
            return [$components[0], $components[1]];
        } elseif (count($components) == 1) {
            // Port is not included - append default port
            return [$components[0], 443];
        } else {
            throw new ValidationException("Invalid apiEndpoint: $apiEndpoint");
        }
    }

    private function replaceEnvVars($key)
    {
        $propertyId = getenv('GA4_PROPERTY_ID');
        if (!empty($propertyId)) {
            $key = str_replace(str_replace('properties/', '', $propertyId), '12345', $key);
        }

        return $key;
    }
}