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
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleGA4ResponseDataTableFactory;

/**
 * @group GoogleAnalyticsImporter
 * @group GoogleAnalyticsImporter_Unit
 */
class GoogleGA4ResponseDataTableFactoryTest extends TestCase
{
    public function test_mergeGaResponse_addsToDataTableCorrectly()
    {
        $instance = new GoogleGA4ResponseDataTableFactory(
            ['someDim', 'someOtherDim'],
            [Metrics::INDEX_REVENUE, Metrics::INDEX_NB_VISITS, Metrics::INDEX_NB_UNIQ_VISITORS],
            ['someMetric', 'someOtherMetric', 'aThirdMetric']);

        // first response
        $r = new \Google\Analytics\Data\V1beta\RunReportResponse();

        $row1 = new \Google\Analytics\Data\V1beta\Row();
        $metric1 = new \Google\Analytics\Data\V1beta\MetricValue();
        $metric1->setValue(3);
        $metric2 = new \Google\Analytics\Data\V1beta\MetricValue();
        $metric2->setValue(4);

        $dimension1 = new \Google\Analytics\Data\V1beta\DimensionValue();
        $dimension1->setValue('a');
        $dimension2 = new \Google\Analytics\Data\V1beta\DimensionValue();
        $dimension2->setValue('c');

        $row1->setMetricValues([$metric1, $metric2]);
        $row1->setDimensionValues([$dimension1, $dimension2]);


        $row2 = new \Google\Analytics\Data\V1beta\Row();
        $metric1 = new \Google\Analytics\Data\V1beta\MetricValue();
        $metric1->setValue(1);
        $metric2 = new \Google\Analytics\Data\V1beta\MetricValue();
        $metric2->setValue(2);

        $dimension1 = new \Google\Analytics\Data\V1beta\DimensionValue();
        $dimension1->setValue('c');
        $dimension2 = new \Google\Analytics\Data\V1beta\DimensionValue();
        $dimension2->setValue('d');

        $row2->setMetricValues([$metric1, $metric2]);
        $row2->setDimensionValues([$dimension1, $dimension2]);

        $r->setRows([$row1,$row2]);

        $instance->mergeGaResponse($r, ['someMetric', 'someOtherMetric']);

        $xml = $this->getAsXml($instance->getDataTable());
        $expectedXml = <<<END
<?xml version="1.0" encoding="utf-8" ?>
<result>
	<row>
		<someMetric>3</someMetric>
		<someOtherMetric>4</someOtherMetric>
		<aThirdMetric>0</aThirdMetric>
		<label>a,c</label>
		<someDim>a</someDim>
		<someOtherDim>c</someOtherDim>
	</row>
	<row>
		<someMetric>1</someMetric>
		<someOtherMetric>2</someOtherMetric>
		<aThirdMetric>0</aThirdMetric>
		<label>c,d</label>
		<someDim>c</someDim>
		<someOtherDim>d</someOtherDim>
	</row>
</result>
END;

        $this->assertEquals($expectedXml, $xml);

        // second response
        $r = new \Google\Analytics\Data\V1beta\RunReportResponse();
        $row1 = new \Google\Analytics\Data\V1beta\Row();
        $metric1 = new \Google\Analytics\Data\V1beta\MetricValue();
        $metric1->setValue(5);
        $metric2 = new \Google\Analytics\Data\V1beta\MetricValue();
        $metric2->setValue(4);

        $dimension1 = new \Google\Analytics\Data\V1beta\DimensionValue();
        $dimension1->setValue('a');
        $dimension2 = new \Google\Analytics\Data\V1beta\DimensionValue();
        $dimension2->setValue('c');

        $row1->setMetricValues([$metric1, $metric2]);
        $row1->setDimensionValues([$dimension1, $dimension2]);


        $row2 = new \Google\Analytics\Data\V1beta\Row();
        $metric1 = new \Google\Analytics\Data\V1beta\MetricValue();
        $metric1->setValue(5);
        $metric2 = new \Google\Analytics\Data\V1beta\MetricValue();
        $metric2->setValue(5);

        $dimension1 = new \Google\Analytics\Data\V1beta\DimensionValue();
        $dimension1->setValue('c');
        $dimension2 = new \Google\Analytics\Data\V1beta\DimensionValue();
        $dimension2->setValue('d');

        $row2->setMetricValues([$metric1, $metric2]);
        $row2->setDimensionValues([$dimension1, $dimension2]);

        $r->setRows([$row1,$row2]);

        $instance->mergeGaResponse($r, ['aThirdMetric']);

        $xml = $this->getAsXml($instance->getDataTable());
        $expectedXml = <<<END
<?xml version="1.0" encoding="utf-8" ?>
<result>
	<row>
		<someMetric>3</someMetric>
		<someOtherMetric>4</someOtherMetric>
		<aThirdMetric>5</aThirdMetric>
		<label>a,c</label>
		<someDim>a</someDim>
		<someOtherDim>c</someOtherDim>
	</row>
	<row>
		<someMetric>1</someMetric>
		<someOtherMetric>2</someOtherMetric>
		<aThirdMetric>5</aThirdMetric>
		<label>c,d</label>
		<someDim>c</someDim>
		<someOtherDim>d</someOtherDim>
	</row>
</result>
END;

        $this->assertEquals($expectedXml, $xml);
    }

    public function test_mergeGaResponse_addsToDataTableCorrectly_ifNoDimensionsUsed()
    {
        $instance = new GoogleGA4ResponseDataTableFactory(
            [],
            [Metrics::INDEX_REVENUE, Metrics::INDEX_NB_VISITS, Metrics::INDEX_NB_UNIQ_VISITORS],
            ['someMetric', 'someOtherMetric', 'aThirdMetric']);

        // first response
        $r = new \Google\Analytics\Data\V1beta\RunReportResponse();
        $row1 = new \Google\Analytics\Data\V1beta\Row();
        $metric1 = new \Google\Analytics\Data\V1beta\MetricValue();
        $metric1->setValue(3);
        $metric2 = new \Google\Analytics\Data\V1beta\MetricValue();
        $metric2->setValue(4);
        $metric3 = new \Google\Analytics\Data\V1beta\MetricValue();
        $metric3->setValue(5);


        $row1->setMetricValues([$metric1, $metric2, $metric3]);
        $row1->setDimensionValues([]);

        $r->setRows([$row1]);

        $instance->mergeGaResponse($r, ['someMetric', 'someOtherMetric', 'aThirdMetric']);

        $xml = $this->getAsXml($instance->getDataTable());
        $expectedXml = <<<END
<?xml version="1.0" encoding="utf-8" ?>
<result>
	<row>
		<someMetric>3</someMetric>
		<someOtherMetric>4</someOtherMetric>
		<aThirdMetric>5</aThirdMetric>
	</row>
</result>
END;

        $this->assertEquals($expectedXml, $xml);
    }

    public function test_convertGaColumnsToMetricIndexes_correctlyConvertsGaMetrics()
    {
        $instance = new GoogleGA4ResponseDataTableFactory(
            ['someDim', 'someOtherDim'],
            [Metrics::INDEX_REVENUE, Metrics::INDEX_NB_VISITS],
            ['someMetric', 'someOtherMetric', 'aThirdMetric']);

        $table = new DataTable();
        $table->addRowsFromArray([
            new Row([
                Row::COLUMNS => [
                    'label' => 'a,b',
                    'someMetric' => 3,
                    'someOtherMetric' => 4,
                    'aThirdMetric' => 5,
                ],
                Row::METADATA => [
                    'someDim' => 'a',
                    'someOtherDim' => 'b',
                ],
            ]),
            new Row([
                Row::COLUMNS => [
                    'label' => 'c,d',
                    'someMetric' => 2,
                    'someOtherMetric' => 2,
                    'aThirdMetric' => 2,
                ],
                Row::METADATA => [
                    'someDim' => 'c',
                    'someOtherDim' => 'd',
                ],
            ]),
        ]);

        $instance->setDataTable($table);

        $instance->convertGaColumnsToMetricIndexes([
            Metrics::INDEX_REVENUE => 'someMetric',
            Metrics::INDEX_NB_VISITS => [
                'metric' => ['someOtherMetric', 'aThirdMetric'],
                'calculate' => function (Row $row) {
                    return $row->getColumn('someOtherMetric') * $row->getColumn('aThirdMetric');
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
		<col name="someDim">a</col>
		<col name="someOtherDim">b</col>
	</row>
	<row>
		<col name="label">c,d</col>
		<col name="9">2</col>
		<col name="2">4</col>
		<col name="someDim">c</col>
		<col name="someOtherDim">d</col>
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