<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Unit\Google;

require_once PIWIK_INCLUDE_PATH . '/plugins/GoogleAnalyticsImporter/vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Piwik\DataTable;
use Piwik\DataTable\Renderer\Xml;
use Piwik\DataTable\Row;
use Piwik\Metrics;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleResponseDataTableFactory;

/**
 * @group GoogleAnalyticsImporter
 * @group GoogleAnalyticsImporter_Unit
 */
class GoogleResponseDataTableFactoryTest extends TestCase
{
    public function test_mergeGaResponse_addsToDataTableCorrectly()
    {
        $instance = new GoogleResponseDataTableFactory(
            ['ga:someDim', 'ga:someOtherDim'],
            [Metrics::INDEX_REVENUE, Metrics::INDEX_NB_VISITS, Metrics::INDEX_NB_UNIQ_VISITORS],
            ['ga:someMetric', 'ga:someOtherMetric', 'ga:aThirdMetric']);

        // first response
        $r = new \Google\Service\AnalyticsReporting\GetReportsResponse();

        $report = new \Google\Service\AnalyticsReporting\Report();
        $r->setReports([$report]);

        $data = new \Google\Service\AnalyticsReporting\ReportData();
        $report->setData($data);

        $rows = [
            new \Google\Service\AnalyticsReporting\ReportRow(),
            new \Google\Service\AnalyticsReporting\ReportRow(),
        ];
        $data->setRows($rows);

        $rows[0]->setDimensions(['a', 'c']);
        $metrics = new \Google\Service\AnalyticsReporting\DateRangeValues();
        $rows[0]->setMetrics([$metrics]);
        $metrics->setValues([3, 4]);

        $rows[1]->setDimensions(['c', 'd']);
        $metrics = new \Google\Service\AnalyticsReporting\DateRangeValues();
        $rows[1]->setMetrics([$metrics]);
        $metrics->setValues([1, 2]);

        $instance->mergeGaResponse($r, ['ga:someMetric', 'ga:someOtherMetric']);

        $xml = $this->getAsXml($instance->getDataTable());
        $expectedXml = <<<END
<?xml version="1.0" encoding="utf-8" ?>
<result>
	<row>
		<ga:someMetric>3</ga:someMetric>
		<ga:someOtherMetric>4</ga:someOtherMetric>
		<ga:aThirdMetric>0</ga:aThirdMetric>
		<label>a,c</label>
		<ga:someDim>a</ga:someDim>
		<ga:someOtherDim>c</ga:someOtherDim>
	</row>
	<row>
		<ga:someMetric>1</ga:someMetric>
		<ga:someOtherMetric>2</ga:someOtherMetric>
		<ga:aThirdMetric>0</ga:aThirdMetric>
		<label>c,d</label>
		<ga:someDim>c</ga:someDim>
		<ga:someOtherDim>d</ga:someOtherDim>
	</row>
</result>
END;

        $this->assertEquals($expectedXml, $xml);

        // second response
        $r = new \Google\Service\AnalyticsReporting\GetReportsResponse();

        $report = new \Google\Service\AnalyticsReporting\Report();
        $r->setReports([$report]);

        $data = new \Google\Service\AnalyticsReporting\ReportData();
        $report->setData($data);

        $rows = [
            new \Google\Service\AnalyticsReporting\ReportRow(),
            new \Google\Service\AnalyticsReporting\ReportRow(),
        ];
        $data->setRows($rows);

        $rows[0]->setDimensions(['a', 'c']);
        $metrics = new \Google\Service\AnalyticsReporting\DateRangeValues();
        $rows[0]->setMetrics([$metrics]);
        $metrics->setValues([5]);

        $rows[1]->setDimensions(['c', 'd']);
        $metrics = new \Google\Service\AnalyticsReporting\DateRangeValues();
        $rows[1]->setMetrics([$metrics]);
        $metrics->setValues([4]);

        $instance->mergeGaResponse($r, ['ga:aThirdMetric']);

        $xml = $this->getAsXml($instance->getDataTable());
        $expectedXml = <<<END
<?xml version="1.0" encoding="utf-8" ?>
<result>
	<row>
		<ga:someMetric>3</ga:someMetric>
		<ga:someOtherMetric>4</ga:someOtherMetric>
		<ga:aThirdMetric>5</ga:aThirdMetric>
		<label>a,c</label>
		<ga:someDim>a</ga:someDim>
		<ga:someOtherDim>c</ga:someOtherDim>
	</row>
	<row>
		<ga:someMetric>1</ga:someMetric>
		<ga:someOtherMetric>2</ga:someOtherMetric>
		<ga:aThirdMetric>4</ga:aThirdMetric>
		<label>c,d</label>
		<ga:someDim>c</ga:someDim>
		<ga:someOtherDim>d</ga:someOtherDim>
	</row>
</result>
END;

        $this->assertEquals($expectedXml, $xml);
    }

    public function test_mergeGaResponse_addsToDataTableCorrectly_ifNoDimensionsUsed()
    {
        $instance = new GoogleResponseDataTableFactory(
            [],
            [Metrics::INDEX_REVENUE, Metrics::INDEX_NB_VISITS, Metrics::INDEX_NB_UNIQ_VISITORS],
            ['ga:someMetric', 'ga:someOtherMetric', 'ga:aThirdMetric']);

        // first response
        $r = new \Google\Service\AnalyticsReporting\GetReportsResponse();

        $report = new \Google\Service\AnalyticsReporting\Report();
        $r->setReports([$report]);

        $data = new \Google\Service\AnalyticsReporting\ReportData();
        $report->setData($data);

        $rows = [
            new \Google\Service\AnalyticsReporting\ReportRow(),
        ];
        $data->setRows($rows);

        $rows[0]->setDimensions([]);
        $metrics = new \Google\Service\AnalyticsReporting\DateRangeValues();
        $rows[0]->setMetrics([$metrics]);
        $metrics->setValues([3, 4, 5]);

        $instance->mergeGaResponse($r, ['ga:someMetric', 'ga:someOtherMetric', 'ga:aThirdMetric']);

        $xml = $this->getAsXml($instance->getDataTable());
        $expectedXml = <<<END
<?xml version="1.0" encoding="utf-8" ?>
<result>
	<row>
		<ga:someMetric>3</ga:someMetric>
		<ga:someOtherMetric>4</ga:someOtherMetric>
		<ga:aThirdMetric>5</ga:aThirdMetric>
	</row>
</result>
END;

        $this->assertEquals($expectedXml, $xml);
    }

    public function test_convertGaColumnsToMetricIndexes_correctlyConvertsGaMetrics()
    {
        $instance = new GoogleResponseDataTableFactory(
            ['ga:someDim', 'ga:someOtherDim'],
            [Metrics::INDEX_REVENUE, Metrics::INDEX_NB_VISITS],
            ['ga:someMetric', 'ga:someOtherMetric', 'ga:aThirdMetric']);

        $table = new DataTable();
        $table->addRowsFromArray([
            new Row([
                Row::COLUMNS => [
                    'label' => 'a,b',
                    'ga:someMetric' => 3,
                    'ga:someOtherMetric' => 4,
                    'ga:aThirdMetric' => 5,
                ],
                Row::METADATA => [
                    'ga:someDim' => 'a',
                    'ga:someOtherDim' => 'b',
                ],
            ]),
            new Row([
                Row::COLUMNS => [
                    'label' => 'c,d',
                    'ga:someMetric' => 2,
                    'ga:someOtherMetric' => 2,
                    'ga:aThirdMetric' => 2,
                ],
                Row::METADATA => [
                    'ga:someDim' => 'c',
                    'ga:someOtherDim' => 'd',
                ],
            ]),
        ]);

        $instance->setDataTable($table);

        $instance->convertGaColumnsToMetricIndexes([
            Metrics::INDEX_REVENUE => 'ga:someMetric',
            Metrics::INDEX_NB_VISITS => [
                'metric' => ['ga:someOtherMetric', 'ga:aThirdMetric'],
                'calculate' => function (Row $row) {
                    return $row->getColumn('ga:someOtherMetric') * $row->getColumn('ga:aThirdMetric');
                },
            ],
        ]);

        $xml = $this->getAsXml($instance->getDataTable());
        $expectedXml = <<<END
<?xml version="1.0" encoding="utf-8" ?>
<result>
	<row>
		<col name="label">a,b</col>
		<col name="9">3</col>
		<col name="2">20</col>
		<col name="ga:someDim">a</col>
		<col name="ga:someOtherDim">b</col>
	</row>
	<row>
		<col name="label">c,d</col>
		<col name="9">2</col>
		<col name="2">4</col>
		<col name="ga:someDim">c</col>
		<col name="ga:someOtherDim">d</col>
	</row>
</result>
END;

        $this->assertEquals($expectedXml, $xml);
    }

    private function getAsXml(\Piwik\DataTable $table)
    {
        $xmlRenderer = new Xml();
        $xmlRenderer->setTable($table);
        return $xmlRenderer->render();
    }
}