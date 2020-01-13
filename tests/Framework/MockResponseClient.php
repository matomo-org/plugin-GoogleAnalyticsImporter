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
use Psr\Http\Message\RequestInterface;

class MockResponseClient extends \Google_Client
{
    private $mockResponses = [];

    public function __construct(array $config = array())
    {
        parent::__construct($config);

        $path = PIWIK_INCLUDE_PATH . CapturingGoogleClient::PATH_TO_CAPTURED_DATA_FILE;
        foreach (new \SplFileObject($path) as $line) {
            $decoded = json_decode($line, $isAssoc = true);

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
        $requestParts = [
            $request->getMethod(),
            $request->getUri()->getAuthority(),
            $request->getUri()->getFragment(),
            $request->getUri()->getHost(),
            $request->getUri()->getPath(),
            $request->getUri()->getPort(),
            $request->getUri()->getQuery(),
            $request->getBody()->getContents(),
        ];

        // for the test induce a failure on a specific day
        if (!$this->hasErroredOnce()
            && Common::isPhpCliMode()
            && strpos($request->getBody()->getContents(), '2019-06-28') === false
        ) {
            sleep(5); // wait 5s to make sure UI test reloads w/ 'started' status
            $this->setErroredOnce();
            throw new \Exception("forced error for test");
        }

        $key = md5(json_encode($requestParts));
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
}