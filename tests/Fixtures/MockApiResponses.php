<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Fixtures;

use Piwik\Date;
use Piwik\Option;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\Authorization;
use Piwik\Plugins\GoogleAnalyticsImporter\tests\Framework\MockResponseClient;
use Piwik\Tests\Framework\Fixture;

class MockApiResponses extends Fixture
{
    public function setUp()
    {
        parent::setUp();

        self::createWebsite('2012-02-02 00:00:00');

        Option::set(Authorization::ACCESS_TOKEN_OPTION_NAME, 'testaccesstoken');
        Option::set(Authorization::CLIENT_CONFIG_OPTION_NAME, json_encode([
            'web' => [
                'client_id' => 'testclientid',
                'project_id' => 'testprojectid',
                'auth_uri' => 'notimportant',
                'token_uri' => 'notimportant',
                'auth_provider_x509_cert_url' => 'certs',
                'client_secret' => 'secret',
            ],
        ]));
    }

    public function provideContainerConfig()
    {
        return [
            'GoogleAnalyticsImporter.googleClientClass' => MockResponseClient::class,
        ];
    }

    private function makeRequestKey(Date $day, array $dimensions, array $metrics, array $options)
    {
        $value = implode('.', [
            $day->toString(),
            json_encode($dimensions),
            json_encode($metrics),
            json_encode($options),
        ]);
        return md5($value);
    }
}