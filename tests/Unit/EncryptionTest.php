<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Unit;

use Piwik\Plugins\GoogleAnalyticsImporter\Configuration;
use Piwik\Plugins\GoogleAnalyticsImporter\Encryption;
use Piwik\Plugins\GoogleAnalyticsImporter\Exceptions\SecretConfigurationException;

/**
 * @group GoogleAnalyticsImporter
 * @group Plugins
 */
class EncryptionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Encryption
     */
    private $encryption;

    public function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('openssl extension is required for the encryption tests');
        }

        $this->encryption = new Encryption($this->makeConfiguration('test-encryption-key'));
    }

    public function testEncryptStringProducesEncryptedPrefixedValue()
    {
        $encrypted = $this->encryption->encryptString('some secret value');

        $this->assertStringStartsWith(Encryption::ENCRYPTED_PREFIX, $encrypted);
        $this->assertNotSame('some secret value', $encrypted);
    }

    public function testEncryptDecryptRoundTrip()
    {
        $plaintext = json_encode(['access_token' => 'abc', 'refresh_token' => 'def']);

        $encrypted = $this->encryption->encryptString($plaintext);
        $decrypted = $this->encryption->decryptString($encrypted);

        $this->assertSame($plaintext, $decrypted);
    }

    public function testEncryptSameValueTwiceProducesDifferentCiphertext()
    {
        $first = $this->encryption->encryptString('same value');
        $second = $this->encryption->encryptString('same value');

        // Random IV per call means the ciphertext must differ.
        $this->assertNotSame($first, $second);
        $this->assertSame('same value', $this->encryption->decryptString($first));
        $this->assertSame('same value', $this->encryption->decryptString($second));
    }

    public function testIsEncrypted()
    {
        $this->assertTrue($this->encryption->isEncrypted($this->encryption->encryptString('x')));
        $this->assertFalse($this->encryption->isEncrypted('plaintext'));
        $this->assertFalse($this->encryption->isEncrypted(''));
        $this->assertFalse($this->encryption->isEncrypted(null));
        $this->assertFalse($this->encryption->isEncrypted(['not', 'a', 'string']));
    }

    public function testDecryptPlaintextIsReturnedUnchanged()
    {
        // Legacy, not-yet-encrypted values must pass through untouched.
        $this->assertSame('plain legacy value', $this->encryption->decryptString('plain legacy value'));
        $this->assertSame('', $this->encryption->decryptString(''));
    }

    public function testDecryptWithWrongKeyThrows()
    {
        $encrypted = $this->encryption->encryptString('secret');

        $otherKeyEncryption = new Encryption($this->makeConfiguration('a-different-key'));

        $this->expectException(SecretConfigurationException::class);
        $otherKeyEncryption->decryptString($encrypted);
    }

    public function testDecryptTamperedValueThrows()
    {
        $encrypted = $this->encryption->encryptString('secret');

        // Flip the payload so the HMAC no longer matches.
        $tampered = $encrypted . 'x';

        $this->expectException(SecretConfigurationException::class);
        $this->encryption->decryptString($tampered);
    }

    private function makeConfiguration(string $key): Configuration
    {
        return new class ($key) extends Configuration {
            /** @var string */
            private $key;

            public function __construct(string $key)
            {
                $this->key = $key;
            }

            public function getEncryptionKey(): string
            {
                return $this->key;
            }

            public function getOrCreateEncryptionKey(): string
            {
                return $this->key;
            }

            public function setEncryptionKey(
                #[\SensitiveParameter]
                string $key
            ): void {
                $this->key = $key;
            }
        };
    }
}
