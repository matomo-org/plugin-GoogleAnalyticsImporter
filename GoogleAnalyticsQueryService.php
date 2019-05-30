<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter;

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

    public function __construct(\Google_Service_Analytics $gaService, $viewId)
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

            $metricsStr = implode(',', $gaMetrics);
            $dimensionsStr = implode(',', $dimensions);

            $response = $this->gaService->data_ga->get('ga:' . $this->viewId, $date, $date, $metricsStr, array_merge([ 'dimensions' => $dimensionsStr ], $options));
            $rows = $response->getRows();

            $this->mergeResult($result, $rows, $gaMetrics, $dimensions);
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
            if (isset($gaMetric['segment'])) {
                $segment = $gaMetric['segment'];
                $queriesBySegment[$segment]['options'] = [
                    'segments' => [$segment],
                ];
            }

            $queriesBySegment[$segment]['metrics'][$index] = $gaMetric;
        }

        return array_values($queriesBySegment);
    }

    // TODO: can probably make some of this code more efficient
    private function mergeResult(DataTable $table, $rows, $gaMetrics, $gaDimensions)
    {
        foreach ($rows as $gaRow) {
            $tableRow = new DataTable\Row();

            $i = 0;

            $label = [];
            foreach ($gaDimensions as $dimension) {
                $labelValue = $gaRow[$i] == '(not set)' ? null : $gaRow[$i];
                $tableRow->setColumn($dimension, $labelValue);

                $label[$dimension] = $labelValue;
                ++$i;
            }
            $label = implode(',', $label); // so we can call getRowFromLabel()

            $tableRow->setColumn('label', $label);

            foreach ($gaMetrics as $metricIndex => $gaMetricName) {
                $tableRow->setColumn($metricIndex, $gaRow[$i]);

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
                'segment' => ['segmentId' => self::GA_SEGMENT_SESSIONS_WITH_CONVERSIONS],
            ],
            Metrics::INDEX_NB_CONVERSIONS => 'ga:goalCompletionsAll',
            Metrics::INDEX_REVENUE => 'ga:totalValue',

            // actions
            // TODO
        ];
    }
}
