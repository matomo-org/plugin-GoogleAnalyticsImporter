<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Framework;

use Piwik\Common;
use Piwik\Option;
use Matomo\Dependencies\GoogleAnalyticsImporter\Psr\Http\Message\RequestInterface;
require_once PIWIK_INCLUDE_PATH . '/plugins/GoogleAnalyticsImporter/vendor/autoload.php';
class MockResponseClient extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Client
{
    public static $isForSystemTest = \false;
    private $mockResponses = [];
    public function __construct(array $config = array())
    {
        parent::__construct($config);
        $path = PIWIK_INCLUDE_PATH . \Piwik\Plugins\GoogleAnalyticsImporter\tests\Framework\CapturingGoogleClient::PATH_TO_CAPTURED_DATA_FILE;
        foreach (new \SplFileObject($path) as $line) {
            $decoded = json_decode($line, $isAssoc = \true);
            $key = md5(json_encode($decoded[0]));
            $value = unserialize($decoded[1]);
            $this->mockResponses[$key] = $value;
        }
    }
    public function authenticate($code)
    {
        return 'testtoken';
    }
    public function execute(RequestInterface $request, $expectedClass = null)
    {
        $requestParts = [$request->getMethod(), $request->getUri()->getAuthority(), $request->getUri()->getFragment(), $request->getUri()->getHost(), $request->getUri()->getPath(), $request->getUri()->getPort(), $request->getUri()->getQuery(), $request->getBody()->getContents()];
        // for the UI test induce a failure on a specific day
        if (!self::$isForSystemTest) {
            if (!$this->hasErroredOnce() && Common::isPhpCliMode() && strpos($request->getBody()->getContents(), '2019-07-02') === \false) {
                sleep(10);
                // wait 10s to make sure UI test reloads w/ 'ongoing' status
                $this->setErroredOnce();
                throw new \Exception("forced error for test");
            }
        }
        $key = json_encode($requestParts);
        $key = $this->replaceEnvVars($key);
        $key = md5($key);
        if (empty($this->mockResponses[$key])) {
            throw new \Exception("Could not find mock response for request: " . json_encode($requestParts));
        }
        return $this->mockResponses[$key];
    }
    private function hasErroredOnce()
    {
        return Option::get('MockResponseClient.hasErroredOnce') == 1;
    }
    private function setErroredOnce()
    {
        Option::set('MockResponseClient.hasErroredOnce', '1');
    }
    private function replaceEnvVars($key)
    {
        $propertyId = getenv('GA_PROPERTY_ID');
        if (!empty($propertyId)) {
            $key = str_replace($propertyId, 'UA-12345-6', $key);
            preg_match('/UA-(.*?)-.*?/', $propertyId, $matches);
            $key = str_replace($matches[1], '12345', $key);
        }
        $viewId = getenv('PIWIK_TEST_GA_VIEW_ID');
        if (!empty($viewId)) {
            $key = str_replace($viewId, '1234567', $key);
        }
        return $key;
    }
}
