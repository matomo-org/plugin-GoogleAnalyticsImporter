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
use Piwik\Plugins\GoogleAnalyticsImporter\Configuration;
use Piwik\Plugins\GoogleAnalyticsImporter\Encryption;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\Authorization;
use Piwik\Plugins\GoogleAnalyticsImporter\Updates_5_2_0;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Updater;

/**
 * @group GoogleAnalyticsImporter
 * @group Plugins
 */
class OAuthCredentialEncryptionTest extends IntegrationTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('openssl extension is required for the encryption tests');
        }
    }

    public function tearDown(): void
    {
        $config = Config::getInstance();
        $config->{Configuration::SECTION_NAME} = [];
        $config->forceSave();

        Option::delete(Authorization::CLIENT_CONFIG_OPTION_NAME);
        Option::delete(Authorization::ACCESS_TOKEN_OPTION_NAME);

        parent::tearDown();
    }

    public function testAuthorizationStoresClientConfigurationEncryptedAndReadsItBack()
    {
        $clientConfig = json_encode(['web' => ['client_id' => 'id123', 'client_secret' => 'secret456']]);

        $authorization = new Authorization();
        $authorization->saveConfig($clientConfig);

        $stored = Option::get(Authorization::CLIENT_CONFIG_OPTION_NAME);
        $this->assertStringStartsWith(Encryption::ENCRYPTED_PREFIX, $stored, 'stored client configuration must be encrypted');
        $this->assertStringNotContainsString('secret456', $stored);

        $this->assertTrue($authorization->hasClientConfiguration());
        $this->assertSame(json_decode($clientConfig, true), $authorization->getClientConfiguration());
    }

    public function testEncryptionKeyIsGeneratedInConfigOnFirstWrite()
    {
        $configuration = new Configuration();
        $this->assertSame('', $configuration->getEncryptionKey());

        (new Authorization())->saveConfig(json_encode(['web' => ['client_id' => 'a', 'client_secret' => 'b']]));

        $this->assertNotSame('', $configuration->getEncryptionKey(), 'encryption key must be persisted to config');
    }

    public function testMigrationEncryptsExistingPlaintextOptions()
    {
        $plaintextConfig = json_encode(['web' => ['client_id' => 'legacyId', 'client_secret' => 'legacySecret']]);
        $plaintextToken = json_encode(['access_token' => 'legacyToken', 'refresh_token' => 'legacyRefresh']);

        // Simulate the pre-5.2.0 plaintext state.
        Option::set(Authorization::CLIENT_CONFIG_OPTION_NAME, $plaintextConfig);
        Option::set(Authorization::ACCESS_TOKEN_OPTION_NAME, $plaintextToken);

        $this->runMigration();

        $encryption = new Encryption();
        $storedConfig = Option::get(Authorization::CLIENT_CONFIG_OPTION_NAME);
        $storedToken = Option::get(Authorization::ACCESS_TOKEN_OPTION_NAME);

        $this->assertTrue($encryption->isEncrypted($storedConfig));
        $this->assertTrue($encryption->isEncrypted($storedToken));
        $this->assertSame($plaintextConfig, $encryption->decryptString($storedConfig));
        $this->assertSame($plaintextToken, $encryption->decryptString($storedToken));

        // Reading through the public API returns the original decrypted values.
        $this->assertSame(json_decode($plaintextConfig, true), (new Authorization())->getClientConfiguration());
    }

    public function testMigrationIsIdempotentAndDoesNotDoubleEncrypt()
    {
        Option::set(Authorization::CLIENT_CONFIG_OPTION_NAME, json_encode(['web' => ['client_id' => 'x', 'client_secret' => 'y']]));

        $this->runMigration();
        $afterFirst = Option::get(Authorization::CLIENT_CONFIG_OPTION_NAME);

        $this->runMigration();
        $afterSecond = Option::get(Authorization::CLIENT_CONFIG_OPTION_NAME);

        $this->assertSame($afterFirst, $afterSecond, 're-running the migration must not double-encrypt');
    }

    public function testReadDegradesGracefullyWhenEncryptionKeyIsInvalid()
    {
        $authorization = new Authorization();
        $authorization->saveConfig(json_encode(['web' => ['client_id' => 'x', 'client_secret' => 'y']]));

        $this->assertTrue($authorization->hasClientConfiguration());

        // Simulate a lost/rotated key.
        (new Configuration())->setEncryptionKey('a-completely-different-key');

        // Must not throw during read; should report "not configured" instead.
        $freshAuthorization = new Authorization();
        $this->assertNull($freshAuthorization->getClientConfiguration());
        $this->assertFalse($freshAuthorization->hasClientConfiguration());
    }

    private function runMigration(): void
    {
        // Update files are named by version (5.2.0.php) and are not PSR-4 autoloaded.
        require_once PIWIK_INCLUDE_PATH . '/plugins/GoogleAnalyticsImporter/Updates/5.2.0.php';

        $migration = new Updates_5_2_0();
        $migration->doUpdate(new Updater());
    }
}
