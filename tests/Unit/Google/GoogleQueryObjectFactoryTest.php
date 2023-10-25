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
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleQueryObjectFactory;
use Piwik\Log\NullLogger;
require_once PIWIK_INCLUDE_PATH . '/plugins/GoogleAnalyticsImporter/vendor/autoload.php';
/**
 * @group GoogleAnalyticsImporter
 * @group GoogleAnalyticsImporter_Unit
 */
class GoogleQueryObjectFactoryTest extends TestCase
{
    /**
     * @var GoogleQueryObjectFactory
     */
    private $instance;
    protected function setUp() : void
    {
        parent::setUp();
        $this->instance = new GoogleQueryObjectFactory(new NullLogger());
    }
    public function test_make_createsProperRequest()
    {
        $metrics = ['ga:somemetric', 'ga:someothermetric', 'ga:thirdmetric'];
        $options = ['dimensions' => ['ga:someDim', 'ga:someOtherDim']];
        $request = $this->instance->make('testviewid', Date::factory('2015-02-04'), $metrics, $options);
        $actual = json_encode($request, \JSON_PRETTY_PRINT);
        $expected = <<<END
{
    "useResourceQuotas": null,
    "reportRequests": [
        {
            "filtersExpression": null,
            "hideTotals": null,
            "hideValueRanges": null,
            "includeEmptyRows": null,
            "pageSize": null,
            "pageToken": null,
            "samplingLevel": null,
            "viewId": "testviewid",
            "dateRanges": [
                {
                    "endDate": "2015-02-04",
                    "startDate": "2015-02-04"
                }
            ],
            "dimensions": [
                {
                    "histogramBuckets": null,
                    "name": "ga:someDim"
                },
                {
                    "histogramBuckets": null,
                    "name": "ga:someOtherDim"
                }
            ],
            "segments": [],
            "metrics": [
                {
                    "alias": null,
                    "expression": "ga:somemetric",
                    "formattingType": null
                },
                {
                    "alias": null,
                    "expression": "ga:someothermetric",
                    "formattingType": null
                },
                {
                    "alias": null,
                    "expression": "ga:thirdmetric",
                    "formattingType": null
                }
            ]
        }
    ]
}
END;
        $this->assertEquals($expected, $actual);
    }
    public function test_make_handlesOrderbyCorrectly()
    {
        $metrics = ['ga:somemetric'];
        $options = ['dimensions' => ['ga:someDim', 'ga:someOtherDim'], 'orderBys' => [['field' => 'ga:someDim', 'order' => 'descENding'], ['field' => 'ga:somemetric', 'order' => 'ascending']]];
        $request = $this->instance->make('testviewid', Date::factory('2015-02-04'), $metrics, $options);
        $actual = json_encode($request, \JSON_PRETTY_PRINT);
        $expected = <<<END
{
    "useResourceQuotas": null,
    "reportRequests": [
        {
            "filtersExpression": null,
            "hideTotals": null,
            "hideValueRanges": null,
            "includeEmptyRows": null,
            "pageSize": null,
            "pageToken": null,
            "samplingLevel": null,
            "viewId": "testviewid",
            "dateRanges": [
                {
                    "endDate": "2015-02-04",
                    "startDate": "2015-02-04"
                }
            ],
            "dimensions": [
                {
                    "histogramBuckets": null,
                    "name": "ga:someDim"
                },
                {
                    "histogramBuckets": null,
                    "name": "ga:someOtherDim"
                }
            ],
            "segments": [],
            "metrics": [
                {
                    "alias": null,
                    "expression": "ga:somemetric",
                    "formattingType": null
                }
            ],
            "orderBys": [
                {
                    "fieldName": "ga:someDim",
                    "orderType": "VALUE",
                    "sortOrder": "DESCENDING"
                },
                {
                    "fieldName": "ga:somemetric",
                    "orderType": "VALUE",
                    "sortOrder": "ASCENDING"
                }
            ]
        }
    ]
}
END;
        $this->assertEquals($expected, $actual);
    }
    public function test_make_handlesShortOrderByCorrectly()
    {
        $metrics = ['ga:somemetric'];
        $options = ['dimensions' => ['ga:someDim', 'ga:someOtherDim'], 'orderBys' => [['field' => 'ga:someDim', 'order' => 'desc'], ['field' => 'ga:somemetric', 'order' => 'ASC']]];
        $request = $this->instance->make('testviewid', Date::factory('2015-02-04'), $metrics, $options);
        $actual = json_encode($request, \JSON_PRETTY_PRINT);
        $expected = <<<END
{
    "useResourceQuotas": null,
    "reportRequests": [
        {
            "filtersExpression": null,
            "hideTotals": null,
            "hideValueRanges": null,
            "includeEmptyRows": null,
            "pageSize": null,
            "pageToken": null,
            "samplingLevel": null,
            "viewId": "testviewid",
            "dateRanges": [
                {
                    "endDate": "2015-02-04",
                    "startDate": "2015-02-04"
                }
            ],
            "dimensions": [
                {
                    "histogramBuckets": null,
                    "name": "ga:someDim"
                },
                {
                    "histogramBuckets": null,
                    "name": "ga:someOtherDim"
                }
            ],
            "segments": [],
            "metrics": [
                {
                    "alias": null,
                    "expression": "ga:somemetric",
                    "formattingType": null
                }
            ],
            "orderBys": [
                {
                    "fieldName": "ga:someDim",
                    "orderType": "VALUE",
                    "sortOrder": "DESCENDING"
                },
                {
                    "fieldName": "ga:somemetric",
                    "orderType": "VALUE",
                    "sortOrder": "ASCENDING"
                }
            ]
        }
    ]
}
END;
        $this->assertEquals($expected, $actual);
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
        return [[['ga:metric1', 'ga:metric2'], ['orderBys' => [['field' => 'somedim1'], ['field' => 'ga:metric2']]], 'ga:metric2'], [['ga:metric1', 'ga:metric2'], ['orderBys' => [['field' => 'ga:metric2'], ['field' => 'somedim1']]], 'ga:metric2'], [['ga:uniquePageviews', 'ga:metric2'], ['orderBys' => [['field' => 'ga:metric3'], ['field' => 'somedim1']]], 'ga:uniquePageviews'], [['ga:metric1', 'ga:uniqueScreenviews'], ['orderBys' => [['field' => 'ga:uniqueScreenviews'], ['field' => 'somedim1']]], 'ga:uniqueScreenviews'], [['ga:metric1', 'ga:pageviews'], ['orderBys' => [['field' => 'ga:pageviews'], ['field' => 'somedim1']]], 'ga:pageviews'], [['ga:metric1', 'ga:screenviews'], ['orderBys' => [['field' => 'ga:pageviews'], ['field' => 'ga:screenviews']]], 'ga:screenviews'], [['ga:metric1', 'ga:sessions'], ['orderBys' => [['field' => 'ga:sessions'], ['field' => 'ga:sessions']]], 'ga:sessions'], [['ga:metric1', 'ga:goalCompletionsAll'], ['orderBys' => [['field' => 'ga:pageviews'], ['field' => 'ga:screenviews']]], 'ga:goalCompletionsAll'], [['ga:sessions', 'ga:uniquePageviews'], [], 'ga:uniquePageviews'], [['ga:uniqueScreenviews', 'ga:alskdjf'], [], 'ga:uniqueScreenviews'], [['ga:metric1', 'ga:pageviews'], [], 'ga:pageviews'], [['ga:metric1', 'ga:screenviews'], [], 'ga:screenviews'], [['ga:metric1', 'ga:sessions'], [], 'ga:sessions'], [['ga:metric1', 'ga:goalCompletionsAll'], [], 'ga:goalCompletionsAll']];
    }
}
