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
use Piwik\Db;
use Piwik\Ini\IniReader;
use Piwik\Option;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\Authorization;
use Piwik\Tests\Framework\Fixture;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;

class ImportedFromGoogle extends Fixture
{
    public $idSite = 1;
    public $dateTime = '2019-06-27 00:00:00';
    public $importedDateRange = '2019-06-24,2019-06-27';

    public $accessToken;
    public $viewId;
    public $clientConfig;

    public function __construct()
    {
        $this->extraPluginsToLoad = ['Funnels'];
    }

    public function setUp()
    {
        parent::setUp();

        $this->getGoogleAnalyticsParams();

        $this->runGoogleImporter();
    }

    private function getGoogleAnalyticsParams()
    {
        if (!getenv('PIWIK_TEST_GA_ACCESS_TOKEN')
            || !getenv('PIWIK_TEST_GA_CLIENT_CONFIG')
        ) {
            $this->tryToUseNonTestEnvCredentials();
        } else {
            $this->accessToken = $this->getEnvVar('PIWIK_TEST_GA_ACCESS_TOKEN');
            $this->clientConfig = $this->getEnvVar('PIWIK_TEST_GA_CLIENT_CONFIG');
        }

        Option::set(Authorization::CLIENT_CONFIG_OPTION_NAME, $this->clientConfig);
        Option::set(Authorization::ACCESS_TOKEN_OPTION_NAME, $this->accessToken);

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
        $property = $this->getEnvVar('GA_PROPERTY_ID');
        $account = $this->getEnvVar('GA_ACCOUNT_ID');

        Option::set(Authorization::ACCESS_TOKEN_OPTION_NAME, $this->accessToken);
        Option::set(Authorization::CLIENT_CONFIG_OPTION_NAME, $this->clientConfig);

        $command = "php " . PIWIK_INCLUDE_PATH . '/tests/PHPUnit/proxy/console ' . $domainParam
            . ' googleanalyticsimporter:import-reports -vvv --view=' . $this->viewId
            . ' --dates=' . $this->importedDateRange . ' --property=' . $property . ' --account=' . $account;

        print "\nImporting from google...\n";

        exec($command, $output, $returnCode);
        $allOutput = implode("\n", $output);

        if ($returnCode) {
            throw new \Exception("GA import failed, code = $returnCode, output: " . $allOutput);
        }

        if (strpos($allOutput, 'Encountered unknown') !== false) {
            throw new \Exception("Found problem warning in GA Import output: " . $allOutput);
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

    private function tryToUseNonTestEnvCredentials()
    {
        Db::destroyDatabaseObject();

        $dbConfig = Config::getInstance()->database;

        try {
            $iniReader = new IniReader();
            $config = $iniReader->readFile(PIWIK_INCLUDE_PATH . '/config/config.ini.php');
            $originalDbName = $config['database']['dbname'];
            $tablesPrefix = $config['database']['tables_prefix'];

            Db::exec("USE $originalDbName");

            $accessToken = Db::fetchOne("SELECT option_value FROM `{$tablesPrefix}option` WHERE option_name = ?",
                [Authorization::ACCESS_TOKEN_OPTION_NAME]);
            if (empty($accessToken)) {
                throw new \Exception("test access token not present as environment variable and not in INI config");
            }
            $this->accessToken = $accessToken;

            $clientConfig = Db::fetchOne("SELECT option_value FROM `{$tablesPrefix}option` WHERE option_name = ?",
                [Authorization::CLIENT_CONFIG_OPTION_NAME]);
            if (empty($clientConfig)) {
                throw new \Exception("test client config not present as environment variable and not in INI config");
            }
            $this->clientConfig = $clientConfig;
        } finally {
            Db::destroyDatabaseObject();
            Config::getInstance()->database = $dbConfig;
        }
    }
}