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
use Piwik\Updates as PiwikUpdates;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\Authorization;

/**
 * Encrypts the OAuth access token and client configuration that were previously stored
 * as plaintext in the option table.
 */
class Updates_5_2_0 extends PiwikUpdates
{
    public function doUpdate(Updater $updater)
    {
        $configuration = new Configuration();
        $configuration->install();

        $encryption = new Encryption($configuration);

        // Both constants resolve to distinct option names; encrypt each stored value once.
        $optionNames = array_unique([
            Authorization::ACCESS_TOKEN_OPTION_NAME,
            Authorization::CLIENT_CONFIG_OPTION_NAME,
        ]);

        foreach ($optionNames as $optionName) {
            $this->encryptOptionIfNeeded($optionName, $encryption);
        }
    }

    private function encryptOptionIfNeeded(string $optionName, Encryption $encryption): void
    {
        $value = Option::get($optionName);

        if (!is_string($value) || $value === '' || $encryption->isEncrypted($value)) {
            return;
        }

        Option::set($optionName, $encryption->encryptString($value));
    }
}
