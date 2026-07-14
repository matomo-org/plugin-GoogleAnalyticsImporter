<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter;

use Piwik\Config;

class Configuration
{
    public const SECTION_NAME = 'GoogleAnalyticsImporter';
    public const KEY_ENCRYPTION_KEY = 'encryption_key';

    public function install(): void
    {
        $this->getOrCreateEncryptionKey();
    }

    public function getEncryptionKey(): string
    {
        $config = $this->getConfig();

        return (string) ($config->{self::SECTION_NAME}[self::KEY_ENCRYPTION_KEY] ?? '');
    }

    public function getOrCreateEncryptionKey(): string
    {
        $key = $this->getEncryptionKey();

        if ($key !== '') {
            return $key;
        }

        $key = base64_encode(random_bytes(32));
        $this->setEncryptionKey($key);

        return $key;
    }

    public function setEncryptionKey(
        #[\SensitiveParameter]
        string $key
    ): void {
        $config = $this->getConfig();
        $pluginConfig = $config->{self::SECTION_NAME} ?: [];
        $pluginConfig[self::KEY_ENCRYPTION_KEY] = $key;
        $config->{self::SECTION_NAME} = $pluginConfig;
        $config->forceSave();
    }

    private function getConfig(): Config
    {
        return Config::getInstance();
    }
}
