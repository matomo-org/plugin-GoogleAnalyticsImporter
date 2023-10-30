<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Framework;

use Piwik\DataTable;
use Piwik\DataTable\Map;
use Piwik\DataTable\Renderer\Xml;
use Piwik\Date;
use Piwik\Filesystem;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleAnalyticsQueryService;
use Piwik\Plugins\GoogleAnalyticsImporter\RecordInserter;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Log\NullLogger;
abstract class BaseRecordImporterTest extends IntegrationTestCase
{
    /**
     * @var Map
     */
    protected $capturedReports;
    public function setUp() : void
    {
        parent::setUp();
        Fixture::createWebsite('2010-02-01 00:00:00');
    }
    abstract function getTestDir();
    abstract function getTestedPluginName();
    protected function runImporterTest($testName, $mockGaResponses, $idSite = 1)
    {
        $this->capturedReports = new Map();
        $this->capturedReports->setKeyName('record');
        $recordImporterClass = 'Piwik\\Plugins\\GoogleAnalyticsImporter\\Importers\\' . $this->getTestedPluginName() . '\\RecordImporter';
        $mockGaQuery = $this->makeMockGaQuery($mockGaResponses);
        /** @var \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporter $instance */
        $instance = new $recordImporterClass($mockGaQuery, $idSite, new NullLogger());
        $instance->setRecordInserter($this->makeMockRecordInserter());
        $instance->importRecords(Date::factory('2013-02-03'));
        $this->checkCapturedReports($testName);
    }
    protected function makeMockGaQuery($responses)
    {
        $mock = $this->getMockBuilder(GoogleAnalyticsQueryService::class)->onlyMethods(['query'])->disableOriginalConstructor()->getMock();
        $mock->method('query')->willReturnCallback(function () use(&$responses) {
            if (empty($responses)) {
                throw new \Exception("out of mock GA responses");
            }
            $rows = array_shift($responses);
            $result = new DataTable();
            foreach ($rows as $row) {
                $newRow = new DataTable\Row();
                foreach ($row as $name => $value) {
                    if (is_numeric($name)) {
                        $newRow->setColumn($name, $value);
                    } else {
                        $newRow->setMetadata($name, $value);
                    }
                }
                $result->addRow($newRow);
            }
            return $result;
        });
        /** @var GoogleAnalyticsQueryService $instance */
        $instance = $mock;
        return $instance;
    }
    protected function makeMockRecordInserter()
    {
        $mock = $this->getMockBuilder(RecordInserter::class)->onlyMethods(['insertNumericRecords', 'insertRecord'])->disableOriginalConstructor()->getMock();
        $mock->method('insertNumericRecords')->willReturnCallback(function ($values) {
            foreach ($values as $name => $value) {
                $table = new DataTable();
                $table->addRowFromSimpleArray(['value' => $value]);
                $this->capturedReports->addTable($table, $name);
            }
        });
        $mock->method('insertRecord')->willReturnCallback(function ($recordName, DataTable $record, $maximumRowsInDataTable = null, $maximumRowsInSubDataTable = null, $columnToSortByBeforeTruncation = null) {
            $clone = $this->copyDataTable($record);
            $this->capturedReports->addTable($clone, $recordName);
        });
        /** @var RecordInserter $inserter */
        $inserter = $mock;
        return $inserter;
    }
    protected function checkCapturedReports($testName)
    {
        $testName = $this->getTestedPluginName() . '_' . $testName;
        $testFilesDirectory = $this->getTestDir() . '/..';
        $processedDir = $testFilesDirectory . '/processed/';
        $expectedDir = $testFilesDirectory . '/expected/';
        $expectedFilePath = $expectedDir . $testName . '.xml';
        $processedFilePath = $processedDir . $testName . '.xml';
        $this->ensureDirectory($processedDir);
        $this->ensureDirectory($expectedDir);
        $renderer = new Xml();
        $renderer->setTable($this->capturedReports);
        $renderer->setRenderSubTables(\true);
        $result = $renderer->render();
        file_put_contents($processedFilePath, $result);
        $expectedContents = file_get_contents($expectedFilePath);
        $this->assertEquals($expectedContents, $result);
    }
    private function copyDataTable(DataTable $record)
    {
        $result = new DataTable();
        foreach ($record->getRows() as $row) {
            $clone = clone $row;
            $subtable = $row->getSubtable();
            if ($subtable) {
                $clone->setSubtable($this->copyDataTable($subtable));
            }
            $result->addRow($clone);
        }
        return $result;
    }
    private function ensureDirectory($expectedDir)
    {
        if (!is_dir($expectedDir)) {
            Filesystem::mkdir($expectedDir);
        }
    }
}
