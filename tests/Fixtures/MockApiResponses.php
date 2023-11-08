<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Fixtures;

use Piwik\Option;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\Authorization;
use Piwik\Plugins\GoogleAnalyticsImporter\tests\Framework\MockResponseClient;
use Piwik\Tests\Framework\Fixture;
class MockApiResponses extends Fixture
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
        Option::set(Authorization::ACCESS_TOKEN_OPTION_NAME, 'testaccesstoken');
        Option::set(Authorization::CLIENT_CONFIG_OPTION_NAME, json_encode(['web' => ['client_id' => 'testclientid', 'project_id' => 'testprojectid', 'auth_uri' => 'notimportant', 'token_uri' => 'notimportant', 'auth_provider_x509_cert_url' => 'certs', 'client_secret' => 'secret']]));
    }
    public function provideContainerConfig()
    {
        return ['GoogleAnalyticsImporter.googleClientClass' => MockResponseClient::class];
    }
}
