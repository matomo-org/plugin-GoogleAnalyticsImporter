<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Framework;

use Matomo\Dependencies\GoogleAnalyticsImporter\Google\ApiCore\Call;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\ApiCore\RequestBuilder;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\ApiCore\Transport\RestTransport;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\ApiCore\Transport\HttpUnaryTransportTrait;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\ApiCore\ValidationException;
use Piwik\Option;
class CaptureRestTransport extends RestTransport
{
    use HttpUnaryTransportTrait;
    public function __construct(RequestBuilder $requestBuilder, callable $httpHandler)
    {
        $this->capturedDataFile = PIWIK_INCLUDE_PATH . \Piwik\Plugins\GoogleAnalyticsImporter\tests\Framework\MockResponseBuilderGA4::PATH_TO_CAPTURED_DATA_FILE;
        if (!is_writable(dirname($this->capturedDataFile))) {
            throw new \Exception("Captured data file is not writable.");
        }
        parent::__construct($requestBuilder, $httpHandler);
    }
    public static function build($apiEndpoint, $restConfigPath, array $config = [])
    {
        $config += ['httpHandler' => null, 'clientCertSource' => null];
        list($baseUri, $port) = self::normalizeServiceAddress($apiEndpoint);
        $requestBuilder = new RequestBuilder("{$baseUri}:{$port}", $restConfigPath);
        $httpHandler = $config['httpHandler'] ?: self::buildHttpHandlerAsync();
        $transport = new \Piwik\Plugins\GoogleAnalyticsImporter\tests\Framework\CaptureRestTransport($requestBuilder, $httpHandler);
        if ($config['clientCertSource']) {
            $transport->configureMtlsChannel($config['clientCertSource']);
        }
        return $transport;
    }
    public function startUnaryCall(Call $call, array $options)
    {
        $response = parent::startUnaryCall($call, $options);
        $response->wait();
        if (isset($options['headers']['x-goog-api-client'])) {
            $options['headers']['x-goog-api-client'] = [];
        }
        $requestParts = [$call->getMethod(), utf8_encode($call->getMessage()->serializeToJsonString()), $call->getCallType(), $call->getDecodeType(), $call->getDescriptor(), $options];
        $entry = json_encode([$requestParts, base64_encode(serialize($response))]);
        $reverse = json_decode($entry, \true);
        $propertyId = getenv('GA4_PROPERTY_ID');
        $entry = str_replace(str_replace('properties/', '', $propertyId), '12345', $entry);
        $streamIds = getenv('GA4_STREAM_IDs');
        $entry = str_replace($streamIds, 'streamId1', $entry);
        $this->saveResponse($entry);
        return $response;
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
            throw new ValidationException("Invalid apiEndpoint: {$apiEndpoint}");
        }
    }
    private function saveResponse($logLine)
    {
        if (!$this->hasWrittenOnce()) {
            file_put_contents($this->capturedDataFile, '');
            $this->setHasWrittenOnce();
        }
        file_put_contents($this->capturedDataFile, $logLine . "\n", \FILE_APPEND);
    }
    private function hasWrittenOnce()
    {
        return Option::get('CapturingGoogleClient.hasWrittenOnceGA4') == 1;
    }
    private function setHasWrittenOnce()
    {
        Option::set('CapturingGoogleClient.hasWrittenOnceGA4', 1);
    }
}
