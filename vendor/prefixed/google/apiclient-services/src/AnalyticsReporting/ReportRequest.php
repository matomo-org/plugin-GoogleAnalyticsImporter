<?php

/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */
namespace Google\Service\AnalyticsReporting;

class ReportRequest extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Collection
{
    protected $collection_key = 'segments';
    protected $cohortGroupType = \Google\Service\AnalyticsReporting\CohortGroup::class;
    protected $cohortGroupDataType = '';
    protected $dateRangesType = \Google\Service\AnalyticsReporting\DateRange::class;
    protected $dateRangesDataType = 'array';
    protected $dimensionFilterClausesType = \Google\Service\AnalyticsReporting\DimensionFilterClause::class;
    protected $dimensionFilterClausesDataType = 'array';
    protected $dimensionsType = \Google\Service\AnalyticsReporting\Dimension::class;
    protected $dimensionsDataType = 'array';
    public $filtersExpression;
    public $hideTotals;
    public $hideValueRanges;
    public $includeEmptyRows;
    protected $metricFilterClausesType = \Google\Service\AnalyticsReporting\MetricFilterClause::class;
    protected $metricFilterClausesDataType = 'array';
    protected $metricsType = \Google\Service\AnalyticsReporting\Metric::class;
    protected $metricsDataType = 'array';
    protected $orderBysType = \Google\Service\AnalyticsReporting\OrderBy::class;
    protected $orderBysDataType = 'array';
    public $pageSize;
    public $pageToken;
    protected $pivotsType = \Google\Service\AnalyticsReporting\Pivot::class;
    protected $pivotsDataType = 'array';
    public $samplingLevel;
    protected $segmentsType = \Google\Service\AnalyticsReporting\Segment::class;
    protected $segmentsDataType = 'array';
    public $viewId;
    /**
     * @param CohortGroup
     */
    public function setCohortGroup(\Google\Service\AnalyticsReporting\CohortGroup $cohortGroup)
    {
        $this->cohortGroup = $cohortGroup;
    }
    /**
     * @return CohortGroup
     */
    public function getCohortGroup()
    {
        return $this->cohortGroup;
    }
    /**
     * @param DateRange[]
     */
    public function setDateRanges($dateRanges)
    {
        $this->dateRanges = $dateRanges;
    }
    /**
     * @return DateRange[]
     */
    public function getDateRanges()
    {
        return $this->dateRanges;
    }
    /**
     * @param DimensionFilterClause[]
     */
    public function setDimensionFilterClauses($dimensionFilterClauses)
    {
        $this->dimensionFilterClauses = $dimensionFilterClauses;
    }
    /**
     * @return DimensionFilterClause[]
     */
    public function getDimensionFilterClauses()
    {
        return $this->dimensionFilterClauses;
    }
    /**
     * @param Dimension[]
     */
    public function setDimensions($dimensions)
    {
        $this->dimensions = $dimensions;
    }
    /**
     * @return Dimension[]
     */
    public function getDimensions()
    {
        return $this->dimensions;
    }
    public function setFiltersExpression($filtersExpression)
    {
        $this->filtersExpression = $filtersExpression;
    }
    public function getFiltersExpression()
    {
        return $this->filtersExpression;
    }
    public function setHideTotals($hideTotals)
    {
        $this->hideTotals = $hideTotals;
    }
    public function getHideTotals()
    {
        return $this->hideTotals;
    }
    public function setHideValueRanges($hideValueRanges)
    {
        $this->hideValueRanges = $hideValueRanges;
    }
    public function getHideValueRanges()
    {
        return $this->hideValueRanges;
    }
    public function setIncludeEmptyRows($includeEmptyRows)
    {
        $this->includeEmptyRows = $includeEmptyRows;
    }
    public function getIncludeEmptyRows()
    {
        return $this->includeEmptyRows;
    }
    /**
     * @param MetricFilterClause[]
     */
    public function setMetricFilterClauses($metricFilterClauses)
    {
        $this->metricFilterClauses = $metricFilterClauses;
    }
    /**
     * @return MetricFilterClause[]
     */
    public function getMetricFilterClauses()
    {
        return $this->metricFilterClauses;
    }
    /**
     * @param Metric[]
     */
    public function setMetrics($metrics)
    {
        $this->metrics = $metrics;
    }
    /**
     * @return Metric[]
     */
    public function getMetrics()
    {
        return $this->metrics;
    }
    /**
     * @param OrderBy[]
     */
    public function setOrderBys($orderBys)
    {
        $this->orderBys = $orderBys;
    }
    /**
     * @return OrderBy[]
     */
    public function getOrderBys()
    {
        return $this->orderBys;
    }
    public function setPageSize($pageSize)
    {
        $this->pageSize = $pageSize;
    }
    public function getPageSize()
    {
        return $this->pageSize;
    }
    public function setPageToken($pageToken)
    {
        $this->pageToken = $pageToken;
    }
    public function getPageToken()
    {
        return $this->pageToken;
    }
    /**
     * @param Pivot[]
     */
    public function setPivots($pivots)
    {
        $this->pivots = $pivots;
    }
    /**
     * @return Pivot[]
     */
    public function getPivots()
    {
        return $this->pivots;
    }
    public function setSamplingLevel($samplingLevel)
    {
        $this->samplingLevel = $samplingLevel;
    }
    public function getSamplingLevel()
    {
        return $this->samplingLevel;
    }
    /**
     * @param Segment[]
     */
    public function setSegments($segments)
    {
        $this->segments = $segments;
    }
    /**
     * @return Segment[]
     */
    public function getSegments()
    {
        return $this->segments;
    }
    public function setViewId($viewId)
    {
        $this->viewId = $viewId;
    }
    public function getViewId()
    {
        return $this->viewId;
    }
}
// Adding a class alias for backwards compatibility with the previous class name.
class_alias(\Google\Service\AnalyticsReporting\ReportRequest::class, 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Service_AnalyticsReporting_ReportRequest');
