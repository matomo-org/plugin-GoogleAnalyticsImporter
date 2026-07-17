<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter;

use Piwik\Piwik;
use Piwik\Plugins\GoogleAnalyticsImporter\Exceptions\SecretConfigurationException;

class Encryption
{
    public const ENCRYPTED_PREFIX = 'enc:v1:';
    private const CIPHER = 'AES-256-CBC';

    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(?Configuration $configuration = null)
    {
        $this->configuration = $configuration ?: new Configuration();
    }

    public function isEncrypted($value): bool
    {
        return is_string($value) && strpos($value, self::ENCRYPTED_PREFIX) === 0;
    }

    public function encryptString(
        #[\SensitiveParameter]
        string $value
    ): string {
        if (!extension_loaded('openssl')) {
            throw new SecretConfigurationException(Piwik::translate('GoogleAnalyticsImporter_EncryptionOpenSslRequiredEncrypt'));
        }

        $key = $this->getEncryptionKey(true);
        $ivLength = (int) openssl_cipher_iv_length(self::CIPHER);
        $iv = random_bytes($ivLength);
        $ciphertext = openssl_encrypt($value, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);

        if (!is_string($ciphertext)) {
            throw new SecretConfigurationException(Piwik::translate('GoogleAnalyticsImporter_EncryptionEncryptFailed'));
        }

        $payload = [
            'iv' => base64_encode($iv),
            'value' => base64_encode($ciphertext),
            'mac' => base64_encode(hash_hmac('sha256', self::ENCRYPTED_PREFIX . $iv . $ciphertext, $key, true)),
        ];

        return self::ENCRYPTED_PREFIX . base64_encode(json_encode($payload));
    }

    public function decryptString(
        #[\SensitiveParameter]
        string $value
    ): string {
        if (!$this->isEncrypted($value)) {
            return $value;
        }

        if (!extension_loaded('openssl')) {
            throw new SecretConfigurationException(Piwik::translate('GoogleAnalyticsImporter_EncryptionOpenSslRequiredDecrypt'));
        }

        $key = $this->getEncryptionKey(false);
        $encodedPayload = substr($value, strlen(self::ENCRYPTED_PREFIX));
        $payload = json_decode(base64_decode($encodedPayload, true) ?: '', true);

        if (empty($payload['iv']) || empty($payload['value']) || empty($payload['mac'])) {
            throw new SecretConfigurationException($this->getInvalidKeyMessage());
        }

        $iv = base64_decode($payload['iv'], true);
        $ciphertext = base64_decode($payload['value'], true);
        $mac = base64_decode($payload['mac'], true);

        if (!is_string($iv) || !is_string($ciphertext) || !is_string($mac)) {
            throw new SecretConfigurationException($this->getInvalidKeyMessage());
        }

        $expectedMac = hash_hmac('sha256', self::ENCRYPTED_PREFIX . $iv . $ciphertext, $key, true);
        if (!hash_equals($expectedMac, $mac)) {
            throw new SecretConfigurationException($this->getInvalidKeyMessage());
        }

        $plaintext = openssl_decrypt($ciphertext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
        if (!is_string($plaintext)) {
            throw new SecretConfigurationException($this->getInvalidKeyMessage());
        }

        return $plaintext;
    }

    private function getEncryptionKey(bool $createIfMissing): string
    {
        $key = $createIfMissing
            ? $this->configuration->getOrCreateEncryptionKey()
            : $this->configuration->getEncryptionKey();

        if ($key === '') {
            throw new SecretConfigurationException($this->getInvalidKeyMessage());
        }

        return hash('sha256', $key, true);
    }

    private function getInvalidKeyMessage(): string
    {
        return Piwik::translate('GoogleAnalyticsImporter_EncryptionKeyMissingOrInvalid');
    }
}
