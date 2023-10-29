<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Framework;

class MockResponseBuilderGA4
{
    const PATH_TO_CAPTURED_DATA_FILE = '/plugins/GoogleAnalyticsImporter/tests/resources/capturedresponses-ga4.log';
    public static $responses = [];
    public static function populateMockResponse()
    {
        if (!empty(self::$responses)) {
            return;
        }
        $capturedDataFile = PIWIK_INCLUDE_PATH . \Piwik\Plugins\GoogleAnalyticsImporter\tests\Framework\MockResponseBuilderGA4::PATH_TO_CAPTURED_DATA_FILE;
        foreach (new \SplFileObject($capturedDataFile) as $line) {
            if (empty($line)) {
                continue;
            }
            $decoded = json_decode($line, $isAssoc = \true);
            $key = md5(json_encode($decoded[0]));
            $value = unserialize(base64_decode($decoded[1]));
            self::$responses[$key] = $value;
        }
    }
}
