<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Unit\Google;

use Piwik\Plugins\GoogleAnalyticsImporter\Google\Authorization;

/**
 * @group GoogleAnalyticsImporter
 * @group Plugins
 */
class AuthorizationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getUnusableConfigs
     */
    public function testValidateConfigRejectsUnusableConfig(string $config, string $expectedMessage): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($expectedMessage);

        (new Authorization())->validateConfig($config);
    }

    public function getUnusableConfigs(): array
    {
        return [
            'empty' => ['', 'GoogleAnalyticsImporter_InvalidClientJson'],
            'not json' => ['not json', 'GoogleAnalyticsImporter_InvalidClientJson'],
            'json null' => ['null', 'GoogleAnalyticsImporter_InvalidClientJson'],
            'empty object' => ['{}', 'GoogleAnalyticsImporter_MissingClientConfiguration'],
            'json scalar' => ['1', 'GoogleAnalyticsImporter_InvalidClientJson'],
            'json list' => ['[1]', 'GoogleAnalyticsImporter_MissingClientConfiguration'],
            'unrelated object' => ['{"foo":"bar"}', 'GoogleAnalyticsImporter_MissingClientConfiguration'],
            'web entry not an object' => ['{"web":"foo"}', 'GoogleAnalyticsImporter_MissingClientConfiguration'],
            'web entry without client ID' => ['{"web":{}}', 'GoogleAnalyticsImporter_MissingClientConfiguration'],
            'installed entry not an object' => ['{"installed":"foo","web":{"client_id":"x","client_secret":"y"}}', 'GoogleAnalyticsImporter_MissingClientConfiguration'],
            'web entry without secret' => ['{"web":{"client_id":"x"}}', 'GoogleAnalyticsImporter_MissingClientConfiguration'],
            'empty secret' => ['{"web":{"client_id":"x","client_secret":""}}', 'GoogleAnalyticsImporter_MissingClientConfiguration'],
            'non-string client ID' => ['{"web":{"client_id":["x"],"client_secret":"y"}}', 'GoogleAnalyticsImporter_MissingClientConfiguration'],
            'installed client' => ['{"installed":{"client_id":"id","client_secret":"secret"}}', 'GoogleAnalyticsImporter_MissingClientConfiguration'],
            'installed alongside web' => ['{"installed":{"client_id":"a","client_secret":"b"},"web":{"client_id":"x","client_secret":"y"}}', 'GoogleAnalyticsImporter_MissingClientConfiguration'],
            'top-level client' => ['{"client_id":"id","client_secret":"secret"}', 'GoogleAnalyticsImporter_MissingClientConfiguration'],
            'service account' => ['{"type":"service_account","client_id":"x","client_email":"a@b.c","private_key":"k"}', 'GoogleAnalyticsImporter_MissingClientConfiguration'],
        ];
    }

    /**
     * @dataProvider getClientConfigs
     */
    public function testValidateConfigAcceptsClientConfig(string $config): void
    {
        $this->expectNotToPerformAssertions();

        (new Authorization())->validateConfig($config);
    }

    public function getClientConfigs(): array
    {
        return [
            'web' => ['{"web":{"client_id":"id","client_secret":"secret"}}'],
        ];
    }
}
