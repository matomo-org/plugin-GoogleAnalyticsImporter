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
use Piwik\Plugins\GoogleAnalyticsImporter\GoogleAnalyticsQueryService;
use Piwik\Plugins\GoogleAnalyticsImporter\Importers\Actions\RecordImporter;
use Piwik\Plugins\GoogleAnalyticsImporter\RecordInserter;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Psr\Log\NullLogger;

abstract class BaseRecordImporterTest extends IntegrationTestCase
{
    /**
     * @var Map
     */
    protected $capturedReports;

    public function setUp()
    {
        parent::setUp();

        Fixture::createWebsite('2010-02-01 00:00:00');
    }

    abstract function getTestDir();

    protected function runImporterTest($testName, $mockGaResponses, $idSite = 1)
    {
        $this->capturedReports = new Map();
        $this->capturedReports->setKeyName('record');

        $mockGaQuery = $this->makeMockGaQuery($mockGaResponses);
        $instance = new RecordImporter($mockGaQuery, $idSite, new NullLogger());
        $instance->setRecordInserter($this->makeMockRecordInserter());

        $instance->importRecords(Date::factory('2013-02-03'));

        $this->checkCapturedReports($testName);
    }

    protected function makeMockGaQuery($responses)
    {
        $mock = $this->getMock(GoogleAnalyticsQueryService::class, ['query'], [], '', $callOriginalConstructor = false);
        $mock->method('query')
            ->willReturnCallback(function () use (&$responses) {
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
        $mock = $this->getMock(RecordInserter::class, ['insertNumericRecords', 'insertRecord'], [], '', $callOriginalConstructor = false);
        $mock->method('insertNumericRecords')
            ->willReturnCallback(function ($values) {
                foreach ($values as $name => $value) {
                    $table = new DataTable();
                    $table->addRowFromSimpleArray(['value' => $value]);
                    $this->capturedReports->addTable($table, $name);
                }
            });
        $mock->method('insertRecord')
            ->willReturnCallback(function ($recordName, DataTable $record, $maximumRowsInDataTable = null,
                                           $maximumRowsInSubDataTable = null, $columnToSortByBeforeTruncation = null) {
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
        $expectedFilePath = $testFilesDirectory . '/expected/' . $testName . '.xml';
        $processedFilePath = $testFilesDirectory . '/processed/' . $testName . '.xml';

        $renderer = new Xml();
        $renderer->setTable($this->capturedReports);
        $renderer->setRenderSubTables(true);
        $result = $renderer->render();

        file_put_contents($processedFilePath, $result);

        $expectedContents = file_get_contents($expectedFilePath);
        $this->assertEquals($expectedContents, $result);
    }

    protected function getTestedPluginName()
    {
        return 'Actions';
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
}