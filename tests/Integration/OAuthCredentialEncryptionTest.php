<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration;

use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\Option;
use Piwik\Plugins\GoogleAnalyticsImporter\Configuration;
use Piwik\Plugins\GoogleAnalyticsImporter\Encryption;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\Authorization;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\AuthorizationGA4;
use Piwik\Plugins\GoogleAnalyticsImporter\Updates_5_2_0;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Updater;
use Piwik\Updater\Migration\Factory as MigrationFactory;

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

        // Config and options are shared across test methods within a process, so reset the
        // encryption key and stored credentials up front to keep each test isolated.
        $this->resetEncryptionState();
    }

    public function tearDown(): void
    {
        $this->resetEncryptionState();

        parent::tearDown();
    }

    private function resetEncryptionState(): void
    {
        $config = Config::getInstance();
        $config->{Configuration::SECTION_NAME} = [];
        $config->forceSave();

        Option::delete(Authorization::CLIENT_CONFIG_OPTION_NAME);
        Option::delete(Authorization::ACCESS_TOKEN_OPTION_NAME);
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

    public function testMigrationDoesNotGenerateEncryptionKeyWhenNothingToEncrypt()
    {
        // No plaintext options, and the encryption key does not exist yet.
        $this->assertSame('', (new Configuration())->getEncryptionKey());

        // Nothing to migrate: the previewable step list must be empty ...
        $this->assertSame([], $this->getMigrations());

        // ... and running the migration must not touch (create) the encryption key. This is
        // what keeps upgrades from failing on a read-only config.ini.php.
        $this->runMigration();
        $this->assertSame('', (new Configuration())->getEncryptionKey(), 'no key should be generated when there is nothing to encrypt');
    }

    public function testMigrationGeneratesEncryptionKeyOnlyWhenPlaintextExists()
    {
        Option::set(Authorization::CLIENT_CONFIG_OPTION_NAME, json_encode(['web' => ['client_id' => 'x', 'client_secret' => 'y']]));

        $this->assertSame('', (new Configuration())->getEncryptionKey());
        $this->assertNotEmpty($this->getMigrations(), 'a migration must be scheduled when plaintext credentials exist');

        $this->runMigration();

        $this->assertNotSame('', (new Configuration())->getEncryptionKey(), 'key must be generated when there is something to encrypt');
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

    public function testGa4ClientConfigurationDecryptsEncryptedCredentials()
    {
        $this->storeEncryptedCredentials();

        $config = $this->resolveGa4ClientConfiguration();

        $this->assertSame('id123', $config['client_id']);
        $this->assertSame('secret456', $config['client_secret']);
        $this->assertSame('refresh789', $config['refresh_token']);
    }

    public function testGa4ClientConfigurationReadsLegacyPlaintextCredentials()
    {
        // Pre-5.2.0 installs may still hold plaintext values that were never migrated.
        Option::set(AuthorizationGA4::CLIENT_CONFIG_OPTION_NAME, json_encode(['web' => ['client_id' => 'legacyId', 'client_secret' => 'legacySecret']]));
        Option::set(AuthorizationGA4::ACCESS_TOKEN_OPTION_NAME, json_encode(['refresh_token' => 'legacyRefresh']));

        $config = $this->resolveGa4ClientConfiguration();

        $this->assertSame('legacyId', $config['client_id']);
        $this->assertSame('legacySecret', $config['client_secret']);
        $this->assertSame('legacyRefresh', $config['refresh_token']);
    }

    public function testGa4ClientConfigurationReturnsEmptyWhenEncryptionKeyIsInvalid()
    {
        $this->storeEncryptedCredentials();

        // Simulate a lost/rotated key.
        (new Configuration())->setEncryptionKey('a-completely-different-key');

        // Must not throw; the DI path should report "not configured" instead.
        $config = $this->resolveGa4ClientConfiguration();

        $this->assertSame('', $config['client_id']);
        $this->assertSame('', $config['client_secret']);
        $this->assertSame('', $config['refresh_token']);
    }

    public function testGa4ClientConfigurationReturnsEmptyWhenEncryptionKeyIsMissing()
    {
        $this->storeEncryptedCredentials();

        // Simulate the key being lost entirely (e.g. config.ini.php not restored with the DB).
        $config = Config::getInstance();
        $config->{Configuration::SECTION_NAME} = [];
        $config->forceSave();

        $resolved = $this->resolveGa4ClientConfiguration();

        $this->assertSame('', $resolved['client_id']);
        $this->assertSame('', $resolved['client_secret']);
        $this->assertSame('', $resolved['refresh_token']);
    }

    private function storeEncryptedCredentials(): void
    {
        $encryption = new Encryption();

        $clientConfig = $encryption->encryptString(json_encode(['web' => ['client_id' => 'id123', 'client_secret' => 'secret456']]));
        $accessToken = $encryption->encryptString(json_encode(['refresh_token' => 'refresh789']));

        Option::set(AuthorizationGA4::CLIENT_CONFIG_OPTION_NAME, $clientConfig);
        Option::set(AuthorizationGA4::ACCESS_TOKEN_OPTION_NAME, $accessToken);

        $this->assertTrue($encryption->isEncrypted($clientConfig));
        $this->assertTrue($encryption->isEncrypted($accessToken));
    }

    /**
     * Resolves the GoogleAnalyticsGA4Importer.clientConfiguration DI definition exactly as
     * AuthorizationGA4 does, so the decrypt-then-decode path is exercised end to end.
     */
    private function resolveGa4ClientConfiguration(): array
    {
        $definitions = require PIWIK_INCLUDE_PATH . '/plugins/GoogleAnalyticsImporter/config/config.php';
        $factory = $definitions['GoogleAnalyticsGA4Importer.clientConfiguration'];

        return $factory(StaticContainer::getContainer());
    }

    /**
     * @return \Piwik\Updater\Migration[]
     */
    private function getMigrations(): array
    {
        return $this->makeMigration()->getMigrations(new Updater());
    }

    private function runMigration(): void
    {
        foreach ($this->getMigrations() as $migration) {
            $migration->exec();
        }
    }

    private function makeMigration(): Updates_5_2_0
    {
        // Update files are named by version (5.2.0.php) and are not PSR-4 autoloaded.
        require_once PIWIK_INCLUDE_PATH . '/plugins/GoogleAnalyticsImporter/Updates/5.2.0.php';

        return new Updates_5_2_0(StaticContainer::get(MigrationFactory::class));
    }
}
