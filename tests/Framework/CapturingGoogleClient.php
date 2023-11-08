<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Framework;

use Piwik\Option;
use Matomo\Dependencies\GoogleAnalyticsImporter\Psr\Http\Message\RequestInterface;
class CapturingGoogleClient extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Client
{
    const PATH_TO_CAPTURED_DATA_FILE = '/plugins/GoogleAnalyticsImporter/tests/resources/capturedresponses.log';
    /**
     * @var string
     */
    private $capturedDataFile;
    public function __construct(array $config = array())
    {
        parent::__construct($config);
        $this->capturedDataFile = PIWIK_INCLUDE_PATH . self::PATH_TO_CAPTURED_DATA_FILE;
        if (!is_writable(dirname($this->capturedDataFile))) {
            throw new \Exception("Captured data file is not writable.");
        }
    }
    public function execute(RequestInterface $request, $expectedClass = null)
    {
        $requestParts = [$request->getMethod(), $request->getUri()->getAuthority(), $request->getUri()->getFragment(), $request->getUri()->getHost(), $request->getUri()->getPath(), $request->getUri()->getPort(), $request->getUri()->getQuery(), $request->getBody()->getContents()];
        $response = parent::execute($request, $expectedClass);
        if ($expectedClass) {
            $entry = json_encode([$requestParts, serialize($response)]);
            $propertyId = getenv('GA_PROPERTY_ID');
            $entry = str_replace($propertyId, 'UA-12345-6', $entry);
            preg_match('/UA-(.*?)-.*?/', $propertyId, $matches);
            $entry = str_replace($matches[1], '12345', $entry);
            $entry = str_replace(getenv('PIWIK_TEST_GA_VIEW_ID'), '1234567', $entry);
            if (!$this->hasWrittenOnce()) {
                file_put_contents($this->capturedDataFile, '');
                $this->setHasWrittenOnce();
            }
            file_put_contents($this->capturedDataFile, $entry . "\n", \FILE_APPEND);
        }
        return $response;
    }
    private function hasWrittenOnce()
    {
        return Option::get('CapturingGoogleClient.hasWrittenOnce') == 1;
    }
    private function setHasWrittenOnce()
    {
        Option::set('CapturingGoogleClient.hasWrittenOnce', 1);
    }
}
