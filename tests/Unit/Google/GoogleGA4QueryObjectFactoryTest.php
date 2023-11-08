<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Unit\Google;

use PHPUnit\Framework\TestCase;
use Piwik\Date;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleGA4QueryObjectFactory;
use Piwik\Log\NullLogger;
require_once PIWIK_INCLUDE_PATH . '/plugins/GoogleAnalyticsImporter/vendor/autoload.php';
/**
 * @group GoogleAnalyticsImporter
 * @group GoogleAnalyticsImporter_Unit
 */
class GoogleGA4QueryObjectFactoryTest extends TestCase
{
    /**
     * @var GoogleGA4QueryObjectFactory
     */
    private $instance;
    protected function setUp() : void
    {
        parent::setUp();
        $this->instance = new GoogleGA4QueryObjectFactory(new NullLogger());
    }
    public function test_make_createsProperRequest()
    {
        $metrics = ['somemetric', 'someothermetric', 'thirdmetric'];
        $options = ['dimensions' => ['someDim', 'someOtherDim']];
        $request = $this->instance->make('testpropertyid', Date::factory('2015-02-04'), $metrics, $options);
        $request = $this->getGaMessageAsJson($request);
        $expected = <<<EOF
{
    "property": "testpropertyid",
    "dateRanges": [
        {
            "startDate": "2015-02-04",
            "endDate": "2015-02-04"
        }
    ],
    "returnPropertyQuota": true,
    "dimensions": [
        {
            "name": "someDim"
        },
        {
            "name": "someOtherDim"
        }
    ],
    "metrics": [
        {
            "name": "somemetric"
        },
        {
            "name": "someothermetric"
        },
        {
            "name": "thirdmetric"
        }
    ],
    "keepEmptyRows": true
}
EOF;
        $this->assertEquals($expected, $request);
    }
    public function test_make_handlesOrderbyCorrectly()
    {
        $metrics = ['somemetric'];
        $options = ['dimensions' => ['someDim', 'someOtherDim'], 'orderBys' => [['field' => 'someDim', 'order' => 'descENding'], ['field' => 'somemetric', 'order' => 'ascending']]];
        $request = $this->instance->make('testpropertyid', Date::factory('2015-02-04'), $metrics, $options);
        $request = $this->getGaMessageAsJson($request);
        $expected = <<<EOF
{
    "property": "testpropertyid",
    "dateRanges": [
        {
            "startDate": "2015-02-04",
            "endDate": "2015-02-04"
        }
    ],
    "returnPropertyQuota": true,
    "orderBys": [
        {
            "dimension": {
                "dimensionName": "someDim"
            }
        }
    ],
    "dimensions": [
        {
            "name": "someDim"
        },
        {
            "name": "someOtherDim"
        }
    ],
    "metrics": [
        {
            "name": "somemetric"
        }
    ],
    "keepEmptyRows": true
}
EOF;
        $this->assertEquals($expected, $request);
    }
    public function test_make_handlesShortOrderByCorrectly()
    {
        $metrics = ['somemetric'];
        $options = ['dimensions' => ['someDim', 'someOtherDim'], 'orderBys' => [['field' => 'someDim', 'order' => 'desc'], ['field' => 'somemetric', 'order' => 'ASC']]];
        $request = $this->instance->make('testpropertyid', Date::factory('2015-02-04'), $metrics, $options);
        $request = $this->getGaMessageAsJson($request);
        $expected = <<<EOF
{
    "property": "testpropertyid",
    "dateRanges": [
        {
            "startDate": "2015-02-04",
            "endDate": "2015-02-04"
        }
    ],
    "returnPropertyQuota": true,
    "orderBys": [
        {
            "dimension": {
                "dimensionName": "someDim"
            },
            "desc": true
        }
    ],
    "dimensions": [
        {
            "name": "someDim"
        },
        {
            "name": "someOtherDim"
        }
    ],
    "metrics": [
        {
            "name": "somemetric"
        }
    ],
    "keepEmptyRows": true
}
EOF;
        $this->assertEquals($expected, $request);
    }
    /**
     * @dataProvider getTestDataForGetOrderByMetric
     */
    public function test_getOrderByMetric_returnsCorrectOrderByMetric($gaMetrics, $queryOptions, $expected)
    {
        $actual = $this->instance->getOrderByMetric($gaMetrics, $queryOptions);
        $this->assertEquals($expected, $actual);
    }
    public function getTestDataForGetOrderByMetric()
    {
        return [[['metric1', 'metric2'], ['orderBys' => [['field' => 'somedim1'], ['field' => 'metric2']]], 'metric2'], [['metric1', 'metric2'], ['orderBys' => [['field' => 'metric2'], ['field' => 'somedim1']]], 'metric2'], [['metric1', 'uniqueScreenviews'], ['orderBys' => [['field' => 'uniqueScreenviews'], ['field' => 'somedim1']]], 'uniqueScreenviews'], [['metric1', 'pageviews'], ['orderBys' => [['field' => 'pageviews'], ['field' => 'somedim1']]], 'pageviews'], [['metric1', 'screenviews'], ['orderBys' => [['field' => 'pageviews'], ['field' => 'screenviews']]], 'screenviews'], [['metric1', 'sessions'], ['orderBys' => [['field' => 'sessions'], ['field' => 'sessions']]], 'sessions'], [['sessions', 'uniquePageviews'], [], 'sessions'], [['screenPageViews', 'alskdjf'], [], 'screenPageViews'], [['metric1', 'sessions'], [], 'sessions'], [['metric1', 'conversions'], [], 'conversions']];
    }
    private function arrayMapRecursive(callable $callback, array $value) : array
    {
        $fn = null;
        $fn = function ($item) use(&$fn, $callback) {
            return is_array($item) ? array_map($fn, $item) : call_user_func($callback, $item);
        };
        return array_map($fn, $value);
    }
    private function getGaMessageAsJson($message) : string
    {
        $message = $this->arrayMapRecursive(function ($item) {
            if (is_object($item) && method_exists($item, 'serializeToJsonString')) {
                return json_decode($item->serializeToJsonString(), \true);
            }
            return $item;
        }, $message);
        $message = json_encode($message, \JSON_PRETTY_PRINT);
        return $message;
    }
}
