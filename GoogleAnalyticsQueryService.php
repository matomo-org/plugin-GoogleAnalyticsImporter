<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter;

use Google_Service_AnalyticsReporting_GetReportsResponse;
use Google_Service_AnalyticsReporting_ReportRow;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Metrics;

class GoogleAnalyticsQueryService
{
    const GA_SEGMENT_SESSIONS_WITH_CONVERSIONS = -9;

    /**
     * @var \Google_Service_Analytics
     */
    private $gaService;

    /**
     * @var string
     */
    private $viewId;

    public function __construct(\Google_Service_AnalyticsReporting $gaService, $viewId)
    {
        $this->gaService = $gaService;
        $this->viewId = $viewId;
    }

    public function query(Date $day, array $dimensions, array $metrics)
    {
        $queries = $this->getQueriesToMake($metrics);

        $date = $day->toString();

        $result = new DataTable();
        foreach ($queries as $query) {
            $gaMetrics = $query['metrics'];
            $options = isset($query['options']) ? $query['options'] : [];

            $response = $this->gaBatchGet($date, $gaMetrics, array_merge([ 'dimensions' => $dimensions ], $options));
            $this->mergeResult($result, $response, $gaMetrics, $dimensions);
        }
        return $result;
    }

    private function getQueriesToMake($metrics)
    {
        $mapping = $this->getMetricIndicesToGaMetrics();

        // TODO: the GA api seems to allow specifying multiple segments. not sure if that means multiple results or if they jsut get and-ed together
        $queriesBySegment = [];
        foreach ($metrics as $index) {
            if (!isset($mapping[$index])) {
                throw new \Exception("Don't know how to map metric index ${index} to GA metric.");
            }

            $gaMetric = $mapping[$index];

            $segment = '';
            $metric = $gaMetric;
            if (isset($gaMetric['segment'])) {
                $metric = $gaMetric['metric'];
                $queriesBySegment[$segment]['options'] = [
                    'segment' => $segment,
                ];

                $segment = json_encode($gaMetric['segment']);
            }

            $queriesBySegment[$segment]['metrics'][$index] = $metric;
        }

        return array_values($queriesBySegment);
    }

    // TODO: can probably make some of this code more efficient
    private function mergeResult(DataTable $table, \Google_Service_AnalyticsReporting_GetReportsResponse $response, $gaMetrics, $gaDimensions)
    {
        /** @var \Google_Service_AnalyticsReporting_Report $gaReport */
        foreach ($response as $gaReport) {
            /** @var \Google_Service_AnalyticsReporting_ReportRow $gaRow */
            foreach ($gaReport->getData()->getRows() as $gaRow) {
                $tableRow = new DataTable\Row();

                $label = [];
                foreach (array_values($gaDimensions) as $index => $dimension) {
                    $labelValue = $gaRow->dimensions[$index] == '(not set)' ? null : $gaRow->dimensions[$index];
                    $tableRow->setMetadata($dimension, $labelValue);

                    $label[$dimension] = $labelValue;
                }
                $label = implode(',', $label); // so we can call getRowFromLabel()

                $tableRow->setColumn('label', $label);

                $gaRowMetrics = $gaRow->getMetrics()[0];

                $i = 0;
                foreach ($gaMetrics as $metricIndex => $gaMetricName) {
                    $tableRow->setColumn($metricIndex, $gaRowMetrics[$i]);
                    ++$i;
                }

                $existingRow = $table->getRowFromLabel($label);
                if (!empty($existingRow)) {
                    $existingRow->sumRow($tableRow);
                } else {
                    $table->addRow($tableRow);
                }
            }
        }
    }

    // TODO: need to map goals + goal metrics
    private function getMetricIndicesToGaMetrics() // TODO: Move to GoogleMetrics class or something
    {
        // TODO: may need to map metric values as well
        return [
            // visit metrics
            Metrics::INDEX_NB_UNIQ_VISITORS => 'ga:users',
            Metrics::INDEX_NB_VISITS => 'ga:sessions',
            Metrics::INDEX_NB_ACTIONS => 'ga:hits',
            Metrics::INDEX_SUM_VISIT_LENGTH => 'ga:sessionDuration',
            Metrics::INDEX_BOUNCE_COUNT => 'ga:bounces',
            Metrics::INDEX_NB_VISITS_CONVERTED => [
                'metric' => 'ga:sessions',
                'segment' => ['segmentId' => 'gaid::' . self::GA_SEGMENT_SESSIONS_WITH_CONVERSIONS],
            ],
            Metrics::INDEX_NB_CONVERSIONS => 'ga:goalCompletionsAll',
            Metrics::INDEX_REVENUE => 'ga:totalValue',

            // actions
            // TODO
        ];
    }

    private function gaBatchGet($date, $gaMetrics, $options)
    {
        $dimensions = [];
        foreach ($options['dimensions'] as $gaDimension) {
            $dimensions[] = $this->makeGaDimension($gaDimension);
        }

        $segments = [];
        if (!empty($options['segment'])) {
            $segments[] = $this->makeGaSegment($options['segment']);
            $dimensions[] = $this->makeGaSegmentDimension();
        }
        
        $metrics = [];
        foreach ($gaMetrics as $gaMetric) {
            $metrics[] = $this->makeGaMetric($gaMetric);
        }

        $request = new \Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($this->viewId);
        $request->setDateRanges([$this->makeGaDateRange($date)]);
        $request->setDimensions($dimensions);
        $request->setSegments($segments);
        $request->setMetrics($metrics);

        $getReport = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        $getReport->setReportRequests([$request]);

        // Call the batchGet method.
        $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests([$request]);
        return $this->gaService->reports->batchGet($body);
    }

    private function makeGaSegment($segment)
    {
        $segmentObj = new \Google_Service_AnalyticsReporting_Segment();
        if (isset($segment['segmentId'])) {
            $segmentObj->setSegmentId($segment['segmentId']);
        } else {
            $segmentObj->setDynamicSegment($segment['dynamicSegment']);
        }
        return $segmentObj;
    }

    private function makeGaDateRange($date)
    {
        $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($date);
        $dateRange->setEndDate($date);
        return $dateRange;
    }

    private function makeGaSegmentDimension()
    {
        $segmentDimensions = new \Google_Service_AnalyticsReporting_Dimension();
        $segmentDimensions->setName("ga:segment");
        return $segmentDimensions;
    }

    private function makeGaDimension($gaDimension)
    {
        $result = new \Google_Service_AnalyticsReporting_Dimension();
        $result->setName($gaDimension);
        return $result;
    }

    private function makeGaMetric($gaMetric)
    {
        $metric = new \Google_Service_AnalyticsReporting_Metric();
        $metric->setExpression($gaMetric);
        return $metric;
    }
}
