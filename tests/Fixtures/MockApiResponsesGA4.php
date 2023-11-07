<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Fixtures;

use Piwik\Option;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\AuthorizationGA4;
use Piwik\Plugins\GoogleAnalyticsImporter\tests\Framework\MockResponseClientGA4;
use Piwik\Plugins\GoogleAnalyticsImporter\tests\Framework\MockResponseAdminServiceClientGA4;
use Piwik\Tests\Framework\Fixture;
class MockApiResponsesGA4 extends Fixture
{
    private $createSite;
    public function __construct($createSite = \true)
    {
        $this->createSite = $createSite;
    }
    public function setUp() : void
    {
        parent::setUp();
        if ($this->createSite) {
            self::createWebsite('2012-02-02 00:00:00');
        }
        Option::set(AuthorizationGA4::ACCESS_TOKEN_OPTION_NAME, json_encode(['access_token' => 'test12345', 'refresh_token' => '123456']));
        Option::set(AuthorizationGA4::CLIENT_CONFIG_OPTION_NAME, json_encode(['web' => ['client_id' => 'testclientid', 'project_id' => 'testprojectid', 'auth_uri' => 'notimportant', 'token_uri' => 'notimportant', 'auth_provider_x509_cert_url' => 'certs', 'client_secret' => 'secret']]));
    }
    public function provideContainerConfig()
    {
        return ['GoogleAnalyticsImporter.googleAnalyticsDataClientClass' => MockResponseClientGA4::class, 'GoogleAnalyticsImporter.googleAnalyticsAdminServiceClientClass' => MockResponseAdminServiceClientGA4::class];
    }
}
