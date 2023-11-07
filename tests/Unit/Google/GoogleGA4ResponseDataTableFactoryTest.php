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
        $instance = new GoogleGA4ResponseDataTableFactory(['someDim', 'someOtherDim'], [Metrics::INDEX_REVENUE, Metrics::INDEX_NB_VISITS, Metrics::INDEX_NB_UNIQ_VISITORS], ['someMetric', 'someOtherMetric', 'aThirdMetric']);
        // first response
        $r = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\RunReportResponse();
        $row1 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\Row();
        $metric1 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\MetricValue();
        $metric1->setValue(3);
        $metric2 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\MetricValue();
        $metric2->setValue(4);
        $dimension1 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\DimensionValue();
        $dimension1->setValue('a');
        $dimension2 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\DimensionValue();
        $dimension2->setValue('c');
        $row1->setMetricValues([$metric1, $metric2]);
        $row1->setDimensionValues([$dimension1, $dimension2]);
        $row2 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\Row();
        $metric1 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\MetricValue();
        $metric1->setValue(1);
        $metric2 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\MetricValue();
        $metric2->setValue(2);
        $dimension1 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\DimensionValue();
        $dimension1->setValue('c');
        $dimension2 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\DimensionValue();
        $dimension2->setValue('d');
        $row2->setMetricValues([$metric1, $metric2]);
        $row2->setDimensionValues([$dimension1, $dimension2]);
        $r->setRows([$row1, $row2]);
        $instance->mergeGaResponse($r, ['someMetric', 'someOtherMetric']);
        $xml = $this->getAsXml($instance->getDataTable());
        $expectedXml = <<<END
<?xml version="1.0" encoding="utf-8" ?>
<result>
\t<row>
\t\t<someMetric>3</someMetric>
\t\t<someOtherMetric>4</someOtherMetric>
\t\t<aThirdMetric>0</aThirdMetric>
\t\t<label>a,c</label>
\t\t<someDim>a</someDim>
\t\t<someOtherDim>c</someOtherDim>
\t</row>
\t<row>
\t\t<someMetric>1</someMetric>
\t\t<someOtherMetric>2</someOtherMetric>
\t\t<aThirdMetric>0</aThirdMetric>
\t\t<label>c,d</label>
\t\t<someDim>c</someDim>
\t\t<someOtherDim>d</someOtherDim>
\t</row>
</result>
END;
        $this->assertEquals($expectedXml, $xml);
        // second response
        $r = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\RunReportResponse();
        $row1 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\Row();
        $metric1 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\MetricValue();
        $metric1->setValue(5);
        $metric2 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\MetricValue();
        $metric2->setValue(4);
        $dimension1 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\DimensionValue();
        $dimension1->setValue('a');
        $dimension2 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\DimensionValue();
        $dimension2->setValue('c');
        $row1->setMetricValues([$metric1, $metric2]);
        $row1->setDimensionValues([$dimension1, $dimension2]);
        $row2 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\Row();
        $metric1 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\MetricValue();
        $metric1->setValue(5);
        $metric2 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\MetricValue();
        $metric2->setValue(5);
        $dimension1 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\DimensionValue();
        $dimension1->setValue('c');
        $dimension2 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\DimensionValue();
        $dimension2->setValue('d');
        $row2->setMetricValues([$metric1, $metric2]);
        $row2->setDimensionValues([$dimension1, $dimension2]);
        $r->setRows([$row1, $row2]);
        $instance->mergeGaResponse($r, ['aThirdMetric']);
        $xml = $this->getAsXml($instance->getDataTable());
        $expectedXml = <<<END
<?xml version="1.0" encoding="utf-8" ?>
<result>
\t<row>
\t\t<someMetric>3</someMetric>
\t\t<someOtherMetric>4</someOtherMetric>
\t\t<aThirdMetric>5</aThirdMetric>
\t\t<label>a,c</label>
\t\t<someDim>a</someDim>
\t\t<someOtherDim>c</someOtherDim>
\t</row>
\t<row>
\t\t<someMetric>1</someMetric>
\t\t<someOtherMetric>2</someOtherMetric>
\t\t<aThirdMetric>5</aThirdMetric>
\t\t<label>c,d</label>
\t\t<someDim>c</someDim>
\t\t<someOtherDim>d</someOtherDim>
\t</row>
</result>
END;
        $this->assertEquals($expectedXml, $xml);
    }
    public function test_mergeGaResponse_addsToDataTableCorrectly_ifNoDimensionsUsed()
    {
        $instance = new GoogleGA4ResponseDataTableFactory([], [Metrics::INDEX_REVENUE, Metrics::INDEX_NB_VISITS, Metrics::INDEX_NB_UNIQ_VISITORS], ['someMetric', 'someOtherMetric', 'aThirdMetric']);
        // first response
        $r = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\RunReportResponse();
        $row1 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\Row();
        $metric1 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\MetricValue();
        $metric1->setValue(3);
        $metric2 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\MetricValue();
        $metric2->setValue(4);
        $metric3 = new \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\MetricValue();
        $metric3->setValue(5);
        $row1->setMetricValues([$metric1, $metric2, $metric3]);
        $row1->setDimensionValues([]);
        $r->setRows([$row1]);
        $instance->mergeGaResponse($r, ['someMetric', 'someOtherMetric', 'aThirdMetric']);
        $xml = $this->getAsXml($instance->getDataTable());
        $expectedXml = <<<END
<?xml version="1.0" encoding="utf-8" ?>
<result>
\t<row>
\t\t<someMetric>3</someMetric>
\t\t<someOtherMetric>4</someOtherMetric>
\t\t<aThirdMetric>5</aThirdMetric>
\t</row>
</result>
END;
        $this->assertEquals($expectedXml, $xml);
    }
    public function test_convertGaColumnsToMetricIndexes_correctlyConvertsGaMetrics()
    {
        $instance = new GoogleGA4ResponseDataTableFactory(['someDim', 'someOtherDim'], [Metrics::INDEX_REVENUE, Metrics::INDEX_NB_VISITS], ['someMetric', 'someOtherMetric', 'aThirdMetric']);
        $table = new DataTable();
        $table->addRowsFromArray([new Row([Row::COLUMNS => ['label' => 'a,b', 'someMetric' => 3, 'someOtherMetric' => 4, 'aThirdMetric' => 5], Row::METADATA => ['someDim' => 'a', 'someOtherDim' => 'b']]), new Row([Row::COLUMNS => ['label' => 'c,d', 'someMetric' => 2, 'someOtherMetric' => 2, 'aThirdMetric' => 2], Row::METADATA => ['someDim' => 'c', 'someOtherDim' => 'd']])]);
        $instance->setDataTable($table);
        $instance->convertGaColumnsToMetricIndexes([Metrics::INDEX_REVENUE => 'someMetric', Metrics::INDEX_NB_VISITS => ['metric' => ['someOtherMetric', 'aThirdMetric'], 'calculate' => function (Row $row) {
            return $row->getColumn('someOtherMetric') * $row->getColumn('aThirdMetric');
        }]]);
        $xml = $this->getAsXml($instance->getDataTable());
        $expectedXml = <<<END
<?xml version="1.0" encoding="utf-8" ?>
<result>
\t<row>
\t\t<col name="label">a,b</col>
\t\t<col name="9">3</col>
\t\t<col name="2">20</col>
\t\t<col name="someDim">a</col>
\t\t<col name="someOtherDim">b</col>
\t</row>
\t<row>
\t\t<col name="label">c,d</col>
\t\t<col name="9">2</col>
\t\t<col name="2">4</col>
\t\t<col name="someDim">c</col>
\t\t<col name="someOtherDim">d</col>
\t</row>
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
