<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration;

use Piwik\Config;
use Piwik\Option;
use Piwik\Piwik;
use Piwik\Plugins\CoreAdminHome\EncryptionKeyRotator;
use Piwik\Plugins\CoreAdminHome\tests\Framework\Mock\FileBackedConfig;
use Piwik\Plugins\GoogleAnalyticsImporter\Configuration;
use Piwik\Plugins\GoogleAnalyticsImporter\Encryption;
use Piwik\Plugins\GoogleAnalyticsImporter\Exceptions\SecretConfigurationException;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\Authorization;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group GoogleAnalyticsImporter
 * @group EncryptionKeyRotationTest
 * @group Plugins
 */
class EncryptionKeyRotationTest extends IntegrationTestCase
{
    private $backupConfig = [];

    /**
     * @var FileBackedConfig|null
     */
    private $config;

    public function setUp(): void
    {
        parent::setUp();

        $this->backupConfig = Config::getInstance()->GoogleAnalyticsImporter ?: [];

        // the rotation command is only available from the Matomo release that introduced it
        if (!class_exists(EncryptionKeyRotator::class)) {
            $this->markTestSkipped('Encryption key rotation is not available in this Matomo version.');
        }

        Config::getInstance()->GoogleAnalyticsImporter = [Configuration::KEY_ENCRYPTION_KEY => 'old-key'];
        $this->config = FileBackedConfig::replaceTestConfig();
    }

    public function tearDown(): void
    {
        if ($this->config !== null) {
            $this->config->deleteFile();
        }

        Config::getInstance()->GoogleAnalyticsImporter = $this->backupConfig;

        parent::tearDown();
    }

    public function testRotationReEncryptsTheOauthCredentialsWithTheNewKey()
    {
        $oldEncryption = Encryption::withKey('old-key');
        Option::set(Authorization::CLIENT_CONFIG_OPTION_NAME, $oldEncryption->encryptString('{"web":{"client_id":"id"}}'));
        Option::set(Authorization::ACCESS_TOKEN_OPTION_NAME, $oldEncryption->encryptString('{"access_token":"token"}'));

        $targets = [];
        Piwik::postEvent('CoreAdminHome.getEncryptionKeyRotationTargets', [&$targets]);
        $count = (new EncryptionKeyRotator())->rotate('GoogleAnalyticsImporter', $targets['GoogleAnalyticsImporter']);

        $this->assertSame(2, $count);

        $newKey = Config::getInstance()->GoogleAnalyticsImporter[Configuration::KEY_ENCRYPTION_KEY];
        $this->assertSame($newKey, $this->config->readFile()['GoogleAnalyticsImporter'][Configuration::KEY_ENCRYPTION_KEY]);
        $this->assertNotSame('old-key', $newKey);

        $newEncryption = Encryption::withKey($newKey);
        $this->assertSame('{"web":{"client_id":"id"}}', $newEncryption->decryptString(Option::get(Authorization::CLIENT_CONFIG_OPTION_NAME)));
        $this->assertSame('{"access_token":"token"}', $newEncryption->decryptString(Option::get(Authorization::ACCESS_TOKEN_OPTION_NAME)));

        $this->expectException(SecretConfigurationException::class);
        $oldEncryption->decryptString(Option::get(Authorization::ACCESS_TOKEN_OPTION_NAME));
    }
}
