<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter;

use Piwik\Option;
use Piwik\Updater;
use Piwik\Updater\Migration\Custom as CustomMigration;
use Piwik\Updater\Migration\Factory as MigrationFactory;
use Piwik\Updates as PiwikUpdates;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\Authorization;

/**
 * Encrypts the OAuth access token and client configuration that were previously stored
 * as plaintext in the option table.
 *
 * The encryption key is only generated (and written to config.ini.php) when there is
 * actually a plaintext credential to migrate. This avoids failing an upgrade with a
 * read-only config.ini.php on installations that have nothing to encrypt.
 */
class Updates_5_2_0 extends PiwikUpdates
{
    /**
     * @var MigrationFactory
     */
    private $migration;

    public function __construct(MigrationFactory $factory)
    {
        $this->migration = $factory;
    }

    public function getMigrations(Updater $updater)
    {
        $encryption = new Encryption();
        $migrations = [];

        foreach ($this->getOptionNames() as $optionName) {
            if (!$this->needsEncryption($optionName, $encryption)) {
                continue;
            }

            $migrations[] = new CustomMigration(function () use ($optionName) {
                $this->encryptOptionIfNeeded($optionName);
            }, 'Encrypt stored Google Analytics Importer credentials (' . $optionName . ')');
        }

        return $migrations;
    }

    public function doUpdate(Updater $updater)
    {
        $updater->executeMigrations(__FILE__, $this->getMigrations($updater));
    }

    /**
     * @return string[]
     */
    private function getOptionNames(): array
    {
        // Both constants resolve to distinct option names; encrypt each stored value once.
        return array_unique([
            Authorization::ACCESS_TOKEN_OPTION_NAME,
            Authorization::CLIENT_CONFIG_OPTION_NAME,
        ]);
    }

    private function needsEncryption(string $optionName, Encryption $encryption): bool
    {
        $value = Option::get($optionName);

        return is_string($value) && $value !== '' && !$encryption->isEncrypted($value);
    }

    private function encryptOptionIfNeeded(string $optionName): void
    {
        // Instantiated inside the migration so the encryption key is only created (and
        // persisted to config.ini.php) when the migration actually runs.
        $encryption = new Encryption();

        if (!$this->needsEncryption($optionName, $encryption)) {
            return;
        }

        Option::set($optionName, $encryption->encryptString(Option::get($optionName)));
    }
}
