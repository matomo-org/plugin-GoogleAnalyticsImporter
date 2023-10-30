<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Fixtures;

use Piwik\Archive\ArchiveInvalidator;
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\CronArchive;
use Piwik\DataAccess\ArchiveTableCreator;
use Piwik\Db;
use Piwik\DbHelper;
use Piwik\Ini\IniReader;
use Piwik\Log\Logger;
use Piwik\Log\LoggerInterface;
use Piwik\Option;
use Piwik\Date;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\Authorization;
use Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus;
use Piwik\Plugins\GoogleAnalyticsImporter\tests\Framework\CapturingGoogleClient;
use Piwik\Plugins\GoogleAnalyticsImporter\tests\Framework\MockResponseClient;
use Piwik\Plugins\VisitsSummary\API;
use Piwik\SettingsPiwik;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\SystemTestCase;
use Piwik\Timer;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;
class ImportedFromGoogle extends Fixture
{
    public $idSite = 1;
    public $dateTime = '2019-06-30 00:00:00';
    public $importedDateRange1 = '2019-06-27,2019-07-01';
    public $importedDateRange2 = '2019-07-02,2019-07-02';
    public $campaignIdSite = 2;
    public $campaignDataDateTime = '2019-08-14';
    public $campaignDataDateRange = '2019-08-14,2019-08-14';
    public $accessToken;
    public $viewId;
    public $clientConfig;
    public $isCapturingResponses;
    public function __construct()
    {
        $this->extraPluginsToLoad = ['Funnels', 'MarketingCampaignsReporting'];
        $this->isCapturingResponses = getenv('MATOMO_TEST_CAPTURE_GA_RESPONSES') == 1;
    }
    public function setUp() : void
    {
        parent::setUp();
        if (SystemTestCase::isCIEnvironment()) {
            $mockResponses = new \Piwik\Plugins\GoogleAnalyticsImporter\tests\Fixtures\MockApiResponses($createSite = \false);
            $mockResponses->setUp();
        }
        $this->getGoogleAnalyticsParams();
        $this->runGoogleImporter($this->importedDateRange1);
        $this->extendEndDate($idSite = 1, '2019-06-27', '2019-07-02');
        $this->scheduleReimport($idSite = 1, '2019-06-27', '2019-06-27');
        $output = $this->runGoogleImporter($this->importedDateRange2, $idSite = 1);
        $this->assertStringContainsString('Importing the following date ranges in order: 2019-06-27,2019-06-27', $output);
        $this->runGoogleImporter($this->campaignDataDateRange);
        $this->aggregateForYear();
        // track a visit on 2019-07-03 and make sure it appears correctly in reports
        $this->trackVisitAfterImport();
        $this->invalidateArchives();
        // trigger core:archive invalidation
        $this->aggregateForYear();
        $this->invalidateAndRearchiveDay();
        // make sure day archives won't be re-archived
        print "Done aggregating.\n";
    }
    private function invalidateArchives()
    {
        ArchiveTableCreator::$tablesAlreadyInstalled = null;
        DbHelper::getTablesInstalled(\true);
        $cronArchive = new CronArchive();
        $cronArchive->init();
        $cronArchive->invalidateArchivedReportsForSitesThatNeedToBeArchivedAgain(1);
        $cronArchive->invalidateArchivedReportsForSitesThatNeedToBeArchivedAgain(2);
    }
    private function aggregateForYear()
    {
        $original = @$_GET['trigger'];
        $_GET['trigger'] = 'archivephp';
        try {
            API::getInstance()->get($this->idSite, 'year', '2019-06-27');
        } finally {
            if (!empty($original)) {
                $_GET['trigger'] = $original;
            } else {
                unset($_GET['trigger']);
            }
        }
    }
    private function trackVisitAfterImport()
    {
        $t = Fixture::getTracker($this->idSite, '2019-07-03 05:23:45');
        $t->setUrl('http://matthieu.net/normal/visit/');
        Fixture::checkResponse($t->doTrackPageView('normal visit'));
        $t->setForceVisitDateTime('2019-07-03 05:25:45');
        $t->setUrl('http://matthieu.net/blog/inde-par-region-et-ville/');
        Fixture::checkResponse($t->doTrackPageView('inde par region et ville'));
    }
    private function getGoogleAnalyticsParams()
    {
        if (SystemTestCase::isCIEnvironment()) {
            $this->viewId = 1234567;
            $this->clientConfig = Option::get(Authorization::CLIENT_CONFIG_OPTION_NAME);
            $this->accessToken = Option::get(Authorization::ACCESS_TOKEN_OPTION_NAME);
        } else {
            if (!getenv('PIWIK_TEST_GA_ACCESS_TOKEN') || !getenv('PIWIK_TEST_GA_CLIENT_CONFIG')) {
                $this->tryToUseNonTestEnvCredentials();
            } else {
                $this->accessToken = $this->getEnvVar('PIWIK_TEST_GA_ACCESS_TOKEN');
                $this->clientConfig = $this->getEnvVar('PIWIK_TEST_GA_CLIENT_CONFIG');
            }
            Option::set(Authorization::CLIENT_CONFIG_OPTION_NAME, $this->clientConfig);
            Option::set(Authorization::ACCESS_TOKEN_OPTION_NAME, $this->accessToken);
            $this->viewId = $this->getEnvVar('PIWIK_TEST_GA_VIEW_ID');
        }
    }
    private function getEnvVar($name)
    {
        $value = getenv($name);
        if (empty($value)) {
            throw new \Exception("The '{$name}' variable must be set for this test.");
        }
        return $value;
    }
    private function runGoogleImporter($dates, $idSiteToResume = null)
    {
        $domain = SettingsPiwik::getPiwikInstanceId();
        $domainParam = $domain ? '--matomo-domain=' . $domain : '';
        if (SystemTestCase::isCIEnvironment()) {
            $property = 'UA-12345-6';
        } else {
            $property = $this->getEnvVar('GA_PROPERTY_ID');
        }
        Option::set(Authorization::ACCESS_TOKEN_OPTION_NAME, $this->accessToken);
        Option::set(Authorization::CLIENT_CONFIG_OPTION_NAME, $this->clientConfig);
        $command = "php " . PIWIK_INCLUDE_PATH . '/tests/PHPUnit/proxy/console ' . $domainParam . ' googleanalyticsimporter:import-reports ';
        if ($idSiteToResume) {
            $command .= '--idsite=' . $idSiteToResume;
        } else {
            $command .= '--view=' . $this->viewId . ' --dates=' . $dates . ' --property=' . $property . ' --extra-custom-dimension=ga:networkLocation,visit';
        }
        $timer = new Timer();
        print "\nImporting from google...\n";
        exec($command, $output, $returnCode);
        $allOutput = implode("\n", $output);
        if ($returnCode) {
            throw new \Exception("GA import failed, code = {$returnCode}, output: " . $allOutput);
        }
        if (strpos($allOutput, 'Encountered unknown') !== \false) {
            throw new \Exception("Found problem warning in GA Import output: " . $allOutput);
        }
        if (stristr($allOutput, 'aborting')) {
            throw new \Exception("GA Import was aborted, output: " . $allOutput);
        }
        print "Done in {$timer}\n";
        return $allOutput;
    }
    public function provideContainerConfig()
    {
        $result = [LoggerInterface::class => \Piwik\DI::get(Logger::class), 'log.handlers' => [\Piwik\DI::get(ConsoleHandler::class)]];
        if (SystemTestCase::isCIEnvironment()) {
            MockResponseClient::$isForSystemTest = \true;
            $result['GoogleAnalyticsImporter.googleClientClass'] = MockResponseClient::class;
        }
        if ($this->isCapturingResponses) {
            $result['GoogleAnalyticsImporter.googleClientClass'] = CapturingGoogleClient::class;
        }
        return $result;
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
            Db::exec("USE {$originalDbName}");
            $accessToken = Db::fetchOne("SELECT option_value FROM `{$tablesPrefix}option` WHERE option_name = ?", [Authorization::ACCESS_TOKEN_OPTION_NAME]);
            if (empty($accessToken)) {
                throw new \Exception("test access token not present as environment variable and not in INI config");
            }
            $this->accessToken = $accessToken;
            $clientConfig = Db::fetchOne("SELECT option_value FROM `{$tablesPrefix}option` WHERE option_name = ?", [Authorization::CLIENT_CONFIG_OPTION_NAME]);
            if (empty($clientConfig)) {
                throw new \Exception("test client config not present as environment variable and not in INI config");
            }
            $this->clientConfig = $clientConfig;
        } finally {
            Db::destroyDatabaseObject();
            Config::getInstance()->database = $dbConfig;
        }
    }
    private function scheduleReimport($idSite, $start, $end)
    {
        /** @var ImportStatus $importStatus */
        $importStatus = StaticContainer::get(ImportStatus::class);
        $importStatus->reImportDateRange($idSite, Date::factory($start), Date::factory($end));
    }
    private function extendEndDate($idSite, $startDate, $endDate)
    {
        /** @var ImportStatus $importStatus */
        $importStatus = StaticContainer::get(ImportStatus::class);
        $importStatus->setImportDateRange($idSite, Date::factory($startDate), Date::factory($endDate));
    }
    private function invalidateAndRearchiveDay()
    {
        ArchiveTableCreator::$tablesAlreadyInstalled = null;
        DbHelper::getTablesInstalled(\true);
        /** @var ArchiveInvalidator $archiveInvalidator */
        $archiveInvalidator = StaticContainer::get(ArchiveInvalidator::class);
        $archiveInvalidator->markArchivesAsInvalidated([$this->idSite], [Date::factory('2019-06-28')], 'day');
        $original = @$_GET['trigger'];
        $_GET['trigger'] = 'archivephp';
        try {
            API::getInstance()->get($this->idSite, 'day', '2019-06-28');
        } finally {
            if (!empty($original)) {
                $_GET['trigger'] = $original;
            } else {
                unset($_GET['trigger']);
            }
        }
    }
}
