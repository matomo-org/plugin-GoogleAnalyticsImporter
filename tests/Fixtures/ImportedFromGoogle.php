<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Fixtures;

use Interop\Container\ContainerInterface;
use Piwik\Config;
use Piwik\Tests\Framework\Fixture;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;

class ImportedFromGoogle extends Fixture
{
    public $idSite = 1;
    public $dateTime = '2018-01-01 00:00:00';
    public $importedDateRange = '2018-12-01,2018-12-31';

    public $accessToken;
    public $viewId;

    public function setUp()
    {
        parent::setUp();

        Fixture::createWebsite('2012-02-03 04:23:45');

        $this->getGoogleAnalyticsParams();

        $this->runGoogleImporter();
    }

    private function getGoogleAnalyticsParams()
    {
        $this->accessToken = $this->getEnvVar('PIWIK_TEST_GA_ACCESS_TOKEN');
        $this->viewId = $this->getEnvVar('PIWIK_TEST_GA_VIEW_ID');
    }

    private function getEnvVar($name)
    {
        $value = getenv($name);
        if (empty($value)) {
            throw new \Exception("The '$name' variable must be set for this test.");
        }
        return $value;
    }

    private function runGoogleImporter()
    {
        $domain = Config::getHostname();
        $domainParam = $domain ? ('--matomo-domain=' . $domain) : '';

        $command = "php " . PIWIK_INCLUDE_PATH . '/tests/PHPUnit/proxy/console ' . $domainParam
            . ' googleanalyticsimporter:import-reports -vvv --view=' . $this->viewId . ' --access-token="' . $this->accessToken . '"'
            . ' --dates=' . $this->importedDateRange . ' --idsite=' . $this->idSite;

        print "\nImporting from google...\n";

        exec($command, $output, $returnCode);
        print implode("\n", $output);
        if ($returnCode) {
            throw new \Exception("GA import failed, code = $returnCode, output: " . implode("\n", $output));
        }
     }

    public function provideContainerConfig()
    {
        return array(
            'Psr\Log\LoggerInterface' => \DI\get('Monolog\Logger'),
            'log.handlers' => [
                \DI\get(ConsoleHandler::class),
            ],
        );
    }
}