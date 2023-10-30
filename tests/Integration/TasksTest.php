<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration;

use Piwik\CliMulti\CliPhp;
use Piwik\Config;
use Piwik\Config\GeneralConfig;
use Piwik\Container\StaticContainer;
use Piwik\Option;
use Piwik\Plugins\GoogleAnalyticsImporter\Commands\ImportReports;
use Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus;
use Piwik\Plugins\GoogleAnalyticsImporter\Tasks;
use Piwik\SettingsPiwik;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Version;
class TasksWithMockExec extends Tasks
{
    public static $commandsRun = [];
    public function __construct()
    {
        self::$commandsRun = [];
    }
    public static function exec($shouldUsePassthru, $command)
    {
        self::$commandsRun[] = [$shouldUsePassthru, $command];
    }
}
/**
 * @group GoogleAnalyticsImporter
 * @group GoogleAnalyticsImporter_Integration
 */
class TasksTest extends IntegrationTestCase
{
    /**
     * @var string
     */
    private $tmpPath;
    public function setUp() : void
    {
        parent::setUp();
        $this->tmpPath = StaticContainer::get('path.tmp');
    }
    public function test_resumeScheduledImports_shouldSkipBrokenStatusEntries_FinishedStatuses_AndImportsThatAreCurrentlyRunning()
    {
        // broken status entries
        Option::set(ImportStatus::OPTION_NAME_PREFIX . 1, json_encode(['status' => ImportStatus::STATUS_STARTED]));
        Option::set(ImportStatus::OPTION_NAME_PREFIX . 2, json_encode(['idSite' => '', 'status' => ImportStatus::STATUS_STARTED]));
        Option::set(ImportStatus::OPTION_NAME_PREFIX . 3, json_encode(['idSite' => 'abc', 'status' => ImportStatus::STATUS_STARTED]));
        // finished status
        Option::set(ImportStatus::OPTION_NAME_PREFIX . 4, json_encode(['idSite' => 4, 'status' => ImportStatus::STATUS_FINISHED]));
        // running status
        Option::set(ImportStatus::OPTION_NAME_PREFIX . 5, json_encode(['idSite' => 5, 'status' => ImportStatus::STATUS_ONGOING]));
        $lock = ImportReports::makeLock();
        $lock->acquireLock(5);
        // empty status
        Option::set(ImportStatus::OPTION_NAME_PREFIX . 6, json_encode(['idSite' => 6]));
        $tasks = new \Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\TasksWithMockExec();
        $tasks->resumeScheduledImports();
        $this->assertEquals([], \Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\TasksWithMockExec::$commandsRun);
    }
    public function test_resumeScheduledImports_runANormalStatusCommandCorrectly()
    {
        Option::set(ImportStatus::OPTION_NAME_PREFIX . 1, json_encode(['idSite' => 1, 'status' => ImportStatus::STATUS_STARTED]));
        $tasks = new \Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\TasksWithMockExec();
        $tasks->resumeScheduledImports();
        $this->assertEquals([
            // nohup /home/travis/.phpenv/versions/7.2.27/bin/php -q /home/travis/build/matomo-org/plugin-GoogleAnalyticsImporter/matomo/tests/PHPUnit/proxy/console --matomo-domain='localhost' googleanalyticsimporter:import-reports --idsite=1 >> /home/travis/build/matomo-org/plugin-GoogleAnalyticsImporter/matomo/tmp/logs/gaimportlog.1.localhost.log 2>&1 &
            [\false, 'nohup ' . $this->getPhpBinary() . ' ' . PIWIK_INCLUDE_PATH . "/tests/PHPUnit/proxy/console{$this->getCommandHostOption()} googleanalyticsimporter:import-reports --idsite=1 >> " . $this->tmpPath . '/logs/gaimportlog.1.' . SettingsPiwik::getPiwikInstanceId() . '.log 2>&1 &'],
        ], \Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\TasksWithMockExec::$commandsRun);
    }
    public function test_resumeScheduledImports_runAStatusWithVerboseLoggingCorrectly()
    {
        Option::set(ImportStatus::OPTION_NAME_PREFIX . 1, json_encode(['idSite' => 1, 'is_verbose_logging_enabled' => 1, 'status' => ImportStatus::STATUS_STARTED]));
        $tasks = new \Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\TasksWithMockExec();
        $tasks->resumeScheduledImports();
        $this->assertEquals([[\false, 'nohup ' . $this->getPhpBinary() . ' ' . PIWIK_INCLUDE_PATH . "/tests/PHPUnit/proxy/console{$this->getCommandHostOption()} googleanalyticsimporter:import-reports --idsite=1 -vvv > " . $this->tmpPath . '/logs/gaimportlog.1.' . SettingsPiwik::getPiwikInstanceId() . '.log 2>&1 &']], \Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\TasksWithMockExec::$commandsRun);
    }
    public function test_resumeScheduledImports_runAStatusWithVerboseLoggingCorrectlyWithInstanceIdUpdated()
    {
        $oldValue = GeneralConfig::getConfigValue('instance_id');
        GeneralConfig::setConfigValue('instance_id', 'touch /tmp/success');
        Option::set(ImportStatus::OPTION_NAME_PREFIX . 1, json_encode(['idSite' => 1, 'is_verbose_logging_enabled' => 1, 'status' => ImportStatus::STATUS_STARTED]));
        $tasks = new \Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\TasksWithMockExec();
        $tasks->resumeScheduledImports();
        $expected = 'nohup ' . $this->getPhpBinary() . ' ' . PIWIK_INCLUDE_PATH . "/tests/PHPUnit/proxy/console{$this->getCommandHostOption()} googleanalyticsimporter:import-reports --idsite=1 -vvv > /dev/null 2>&1 &";
        //since instance id is now sanitized in Matomo 5.x
        if (version_compare(Version::VERSION, '5.0.0-b1', '>=')) {
            $expected = 'nohup ' . $this->getPhpBinary() . ' ' . PIWIK_INCLUDE_PATH . "/tests/PHPUnit/proxy/console{$this->getCommandHostOption()} googleanalyticsimporter:import-reports --idsite=1 -vvv > " . PIWIK_INCLUDE_PATH . '/tmp/logs/gaimportlog.1.touchtmpsuccess.log' . " 2>&1 &";
        }
        $this->assertEquals([[\false, $expected]], \Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\TasksWithMockExec::$commandsRun);
        GeneralConfig::setConfigValue('instance_id', $oldValue);
    }
    public function test_resumeScheduledImports_runAStatusWithVerboseLoggingCorrectlyWithInstanceIdUpdated_1()
    {
        $oldValue = GeneralConfig::getConfigValue('instance_id');
        GeneralConfig::setConfigValue('instance_id', 'test; rm -rf .');
        Option::set(ImportStatus::OPTION_NAME_PREFIX . 1, json_encode(['idSite' => 1, 'is_verbose_logging_enabled' => 1, 'status' => ImportStatus::STATUS_STARTED]));
        $tasks = new \Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\TasksWithMockExec();
        $tasks->resumeScheduledImports();
        $expected = 'nohup ' . $this->getPhpBinary() . ' ' . PIWIK_INCLUDE_PATH . "/tests/PHPUnit/proxy/console{$this->getCommandHostOption()} googleanalyticsimporter:import-reports --idsite=1 -vvv > " . $this->tmpPath . '/logs/gaimportlog.1.test\\; rm -rf ..log 2>&1 &';
        //since instance id is now sanitized in Matomo 5.x
        if (version_compare(Version::VERSION, '5.0.0-b1', '>=')) {
            $expected = 'nohup ' . $this->getPhpBinary() . ' ' . PIWIK_INCLUDE_PATH . "/tests/PHPUnit/proxy/console{$this->getCommandHostOption()} googleanalyticsimporter:import-reports --idsite=1 -vvv > " . $this->tmpPath . '/logs/gaimportlog.1.testrm-rf..log 2>&1 &';
        }
        $this->assertEquals([[\false, $expected]], \Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\TasksWithMockExec::$commandsRun);
        GeneralConfig::setConfigValue('instance_id', $oldValue);
    }
    public function test_archiveImportedReports_shouldSkipBrokenStatusEntries_ImportsThatHaveNotImportedAnything_OrLastArchivedDateIsEqualOrGreaterToLastImportedDate()
    {
        // broken status entries
        Option::set(ImportStatus::OPTION_NAME_PREFIX . 1, json_encode(['last_date_imported' => '2012-02-02', 'status' => ImportStatus::STATUS_STARTED]));
        Option::set(ImportStatus::OPTION_NAME_PREFIX . 2, json_encode(['last_date_imported' => '2012-02-02', 'idSite' => '', 'status' => ImportStatus::STATUS_STARTED]));
        Option::set(ImportStatus::OPTION_NAME_PREFIX . 3, json_encode(['last_date_imported' => '2012-02-02', 'idSite' => 'abc', 'status' => ImportStatus::STATUS_STARTED]));
        // no or broken last_date_imported
        Option::set(ImportStatus::OPTION_NAME_PREFIX . 4, json_encode(['idSite' => 4, 'status' => ImportStatus::STATUS_STARTED]));
        Option::set(ImportStatus::OPTION_NAME_PREFIX . 5, json_encode(['idSite' => 5, 'last_date_imported' => 'aslkdjf', 'status' => ImportStatus::STATUS_STARTED]));
        // broken last_day_archived
        Option::set(ImportStatus::OPTION_NAME_PREFIX . 6, json_encode(['idSite' => 6, 'last_date_imported' => '2012-02-02', 'last_day_archived' => 'sdlfkjdsf', 'status' => ImportStatus::STATUS_STARTED]));
        // last_day_archived == or earlier to last_date_imported
        Option::set(ImportStatus::OPTION_NAME_PREFIX . 7, json_encode(['idSite' => 7, 'last_date_imported' => '2012-02-02', 'last_day_archived' => '2012-02-02', 'status' => ImportStatus::STATUS_STARTED]));
        Option::set(ImportStatus::OPTION_NAME_PREFIX . 8, json_encode(['idSite' => 8, 'last_date_imported' => '2012-02-02', 'last_day_archived' => '2012-04-04', 'status' => ImportStatus::STATUS_STARTED]));
        // running status
        Option::set(ImportStatus::OPTION_NAME_PREFIX . 9, json_encode(['last_date_imported' => '2012-02-02', 'idSite' => 9, 'status' => ImportStatus::STATUS_STARTED]));
        $lock = ImportReports::makeLock();
        $lock->acquireLock(9);
        // empty status
        Option::set(ImportStatus::OPTION_NAME_PREFIX . 10, json_encode(['idSite' => 10]));
        $tasks = new \Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\TasksWithMockExec();
        $tasks->archiveImportedReports();
        $this->assertEquals([[\false, 'MATOMO_GOOGLE_IMPORT_END_DATE_TO_ARCHIVE=2012-02-02 nohup ' . $this->getPhpBinary() . ' ' . PIWIK_INCLUDE_PATH . '/tests/PHPUnit/proxy/console' . $this->getCommandHostOption() . ' core:archive --disable-scheduled-tasks --force-idsites=7 --force-periods=week,month,year --force-date-range=2012-02-02,2012-02-02 > ' . PIWIK_INCLUDE_PATH . '/tmp/logs/gaimportlog.archive.7.' . SettingsPiwik::getPiwikInstanceId() . '.log 2>&1 &']], \Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\TasksWithMockExec::$commandsRun);
    }
    public function test_archiveImportedReports_shouldRunTheCommandCorrectly()
    {
        Fixture::createWebsite('2012-01-15');
        Option::set(ImportStatus::OPTION_NAME_PREFIX . 1, json_encode(['idSite' => 1, 'last_date_imported' => '2012-02-02', 'status' => ImportStatus::STATUS_STARTED]));
        $tasks = new \Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\TasksWithMockExec();
        $tasks->archiveImportedReports();
        $this->assertEquals([[\false, 'MATOMO_GOOGLE_IMPORT_END_DATE_TO_ARCHIVE=2012-02-02 nohup ' . $this->getPhpBinary() . ' ' . PIWIK_INCLUDE_PATH . "/tests/PHPUnit/proxy/console{$this->getCommandHostOption()} core:archive --disable-scheduled-tasks --force-idsites=1 --force-periods=week,month,year --force-date-range=2012-01-14,2012-02-02 > " . $this->tmpPath . '/logs/gaimportlog.archive.1.' . SettingsPiwik::getPiwikInstanceId() . '.log 2>&1 &']], \Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\TasksWithMockExec::$commandsRun);
    }
    public function test_archiveImportedReports_shouldRunTheCommandCorrectly_ifLastDayArchivedPresent()
    {
        Option::set(ImportStatus::OPTION_NAME_PREFIX . 1, json_encode(['idSite' => 1, 'last_date_imported' => '2012-02-02', 'last_day_archived' => '2012-01-02', 'status' => ImportStatus::STATUS_STARTED]));
        $tasks = new \Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\TasksWithMockExec();
        $tasks->archiveImportedReports();
        $this->assertEquals([[\false, 'MATOMO_GOOGLE_IMPORT_END_DATE_TO_ARCHIVE=2012-02-02 nohup ' . $this->getPhpBinary() . ' ' . PIWIK_INCLUDE_PATH . "/tests/PHPUnit/proxy/console{$this->getCommandHostOption()} core:archive --disable-scheduled-tasks --force-idsites=1 --force-periods=week,month,year --force-date-range=2012-01-02,2012-02-02 > " . $this->tmpPath . '/logs/gaimportlog.archive.1.' . SettingsPiwik::getPiwikInstanceId() . '.log 2>&1 &']], \Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\TasksWithMockExec::$commandsRun);
    }
    public function test_archiveImportedReports_shouldRunTheCommandCorrectly_ifCustomRangeStartTime()
    {
        Option::set(ImportStatus::OPTION_NAME_PREFIX . 1, json_encode(['idSite' => 1, 'last_date_imported' => '2012-02-02', 'import_range_start' => '2012-01-20', 'status' => ImportStatus::STATUS_STARTED]));
        $tasks = new \Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\TasksWithMockExec();
        $tasks->archiveImportedReports();
        $this->assertEquals([[\false, 'MATOMO_GOOGLE_IMPORT_END_DATE_TO_ARCHIVE=2012-02-02 nohup ' . $this->getPhpBinary() . ' ' . PIWIK_INCLUDE_PATH . "/tests/PHPUnit/proxy/console{$this->getCommandHostOption()} core:archive --disable-scheduled-tasks --force-idsites=1 --force-periods=week,month,year --force-date-range=2012-01-20,2012-02-02 > " . $this->tmpPath . '/logs/gaimportlog.archive.1.' . SettingsPiwik::getPiwikInstanceId() . '.log 2>&1 &']], \Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\TasksWithMockExec::$commandsRun);
    }
    private function getCommandHostOption()
    {
        $host = SettingsPiwik::getPiwikInstanceId();
        if (!empty($host)) {
            return " --matomo-domain='{$host}'";
        }
        return '';
    }
    private function getPhpBinary()
    {
        $cliPhp = new CliPhp();
        return $cliPhp->findPhpBinary();
    }
}
