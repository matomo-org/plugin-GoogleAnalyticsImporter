<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/analytics/data/v1alpha/analytics_data_api.proto
namespace Google\Analytics\Data\V1alpha;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;
/**
 * The response report table corresponding to a request.
 *
 * Generated from protobuf message <code>google.analytics.data.v1alpha.RunReportResponse</code>
 */
class RunReportResponse extends \Google\Protobuf\Internal\Message
{
    /**
     * Describes dimension columns. The number of DimensionHeaders and ordering of
     * DimensionHeaders matches the dimensions present in rows.
     *
     * Generated from protobuf field <code>repeated .google.analytics.data.v1alpha.DimensionHeader dimension_headers = 11;</code>
     */
    private $dimension_headers;
    /**
     * Describes metric columns. The number of MetricHeaders and ordering of
     * MetricHeaders matches the metrics present in rows.
     *
     * Generated from protobuf field <code>repeated .google.analytics.data.v1alpha.MetricHeader metric_headers = 1;</code>
     */
    private $metric_headers;
    /**
     * Rows of dimension value combinations and metric values in the report.
     *
     * Generated from protobuf field <code>repeated .google.analytics.data.v1alpha.Row rows = 2;</code>
     */
    private $rows;
    /**
     * If requested, the totaled values of metrics.
     *
     * Generated from protobuf field <code>repeated .google.analytics.data.v1alpha.Row totals = 8;</code>
     */
    private $totals;
    /**
     * If requested, the maximum values of metrics.
     *
     * Generated from protobuf field <code>repeated .google.analytics.data.v1alpha.Row maximums = 9;</code>
     */
    private $maximums;
    /**
     * If requested, the minimum values of metrics.
     *
     * Generated from protobuf field <code>repeated .google.analytics.data.v1alpha.Row minimums = 10;</code>
     */
    private $minimums;
    /**
     * The total number of rows in the query result, regardless of the number of
     * rows returned in the response. For example if a query returns 175 rows and
     * includes limit = 50 in the API request, the response will contain row_count
     * = 175 but only 50 rows.
     * To learn more about this pagination parameter, see
     * [Pagination](https://developers.google.com/analytics/devguides/reporting/data/v1/basics#pagination).
     *
     * Generated from protobuf field <code>int32 row_count = 12;</code>
     */
    private $row_count = 0;
    /**
     * Metadata for the report.
     *
     * Generated from protobuf field <code>.google.analytics.data.v1alpha.ResponseMetaData metadata = 6;</code>
     */
    private $metadata = null;
    /**
     * This Analytics Property's quota state including this request.
     *
     * Generated from protobuf field <code>.google.analytics.data.v1alpha.PropertyQuota property_quota = 7;</code>
     */
    private $property_quota = null;
    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Analytics\Data\V1alpha\DimensionHeader[]|\Google\Protobuf\Internal\RepeatedField $dimension_headers
     *           Describes dimension columns. The number of DimensionHeaders and ordering of
     *           DimensionHeaders matches the dimensions present in rows.
     *     @type \Google\Analytics\Data\V1alpha\MetricHeader[]|\Google\Protobuf\Internal\RepeatedField $metric_headers
     *           Describes metric columns. The number of MetricHeaders and ordering of
     *           MetricHeaders matches the metrics present in rows.
     *     @type \Google\Analytics\Data\V1alpha\Row[]|\Google\Protobuf\Internal\RepeatedField $rows
     *           Rows of dimension value combinations and metric values in the report.
     *     @type \Google\Analytics\Data\V1alpha\Row[]|\Google\Protobuf\Internal\RepeatedField $totals
     *           If requested, the totaled values of metrics.
     *     @type \Google\Analytics\Data\V1alpha\Row[]|\Google\Protobuf\Internal\RepeatedField $maximums
     *           If requested, the maximum values of metrics.
     *     @type \Google\Analytics\Data\V1alpha\Row[]|\Google\Protobuf\Internal\RepeatedField $minimums
     *           If requested, the minimum values of metrics.
     *     @type int $row_count
     *           The total number of rows in the query result, regardless of the number of
     *           rows returned in the response. For example if a query returns 175 rows and
     *           includes limit = 50 in the API request, the response will contain row_count
     *           = 175 but only 50 rows.
     *           To learn more about this pagination parameter, see
     *           [Pagination](https://developers.google.com/analytics/devguides/reporting/data/v1/basics#pagination).
     *     @type \Google\Analytics\Data\V1alpha\ResponseMetaData $metadata
     *           Metadata for the report.
     *     @type \Google\Analytics\Data\V1alpha\PropertyQuota $property_quota
     *           This Analytics Property's quota state including this request.
     * }
     */
    public function __construct($data = NULL)
    {
        \GPBMetadata\Google\Analytics\Data\V1Alpha\AnalyticsDataApi::initOnce();
        parent::__construct($data);
    }
    /**
     * Describes dimension columns. The number of DimensionHeaders and ordering of
     * DimensionHeaders matches the dimensions present in rows.
     *
     * Generated from protobuf field <code>repeated .google.analytics.data.v1alpha.DimensionHeader dimension_headers = 11;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getDimensionHeaders()
    {
        return $this->dimension_headers;
    }
    /**
     * Describes dimension columns. The number of DimensionHeaders and ordering of
     * DimensionHeaders matches the dimensions present in rows.
     *
     * Generated from protobuf field <code>repeated .google.analytics.data.v1alpha.DimensionHeader dimension_headers = 11;</code>
     * @param \Google\Analytics\Data\V1alpha\DimensionHeader[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setDimensionHeaders($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Analytics\Data\V1alpha\DimensionHeader::class);
        $this->dimension_headers = $arr;
        return $this;
    }
    /**
     * Describes metric columns. The number of MetricHeaders and ordering of
     * MetricHeaders matches the metrics present in rows.
     *
     * Generated from protobuf field <code>repeated .google.analytics.data.v1alpha.MetricHeader metric_headers = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getMetricHeaders()
    {
        return $this->metric_headers;
    }
    /**
     * Describes metric columns. The number of MetricHeaders and ordering of
     * MetricHeaders matches the metrics present in rows.
     *
     * Generated from protobuf field <code>repeated .google.analytics.data.v1alpha.MetricHeader metric_headers = 1;</code>
     * @param \Google\Analytics\Data\V1alpha\MetricHeader[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setMetricHeaders($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Analytics\Data\V1alpha\MetricHeader::class);
        $this->metric_headers = $arr;
        return $this;
    }
    /**
     * Rows of dimension value combinations and metric values in the report.
     *
     * Generated from protobuf field <code>repeated .google.analytics.data.v1alpha.Row rows = 2;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getRows()
    {
        return $this->rows;
    }
    /**
     * Rows of dimension value combinations and metric values in the report.
     *
     * Generated from protobuf field <code>repeated .google.analytics.data.v1alpha.Row rows = 2;</code>
     * @param \Google\Analytics\Data\V1alpha\Row[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setRows($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Analytics\Data\V1alpha\Row::class);
        $this->rows = $arr;
        return $this;
    }
    /**
     * If requested, the totaled values of metrics.
     *
     * Generated from protobuf field <code>repeated .google.analytics.data.v1alpha.Row totals = 8;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getTotals()
    {
        return $this->totals;
    }
    /**
     * If requested, the totaled values of metrics.
     *
     * Generated from protobuf field <code>repeated .google.analytics.data.v1alpha.Row totals = 8;</code>
     * @param \Google\Analytics\Data\V1alpha\Row[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setTotals($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Analytics\Data\V1alpha\Row::class);
        $this->totals = $arr;
        return $this;
    }
    /**
     * If requested, the maximum values of metrics.
     *
     * Generated from protobuf field <code>repeated .google.analytics.data.v1alpha.Row maximums = 9;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getMaximums()
    {
        return $this->maximums;
    }
    /**
     * If requested, the maximum values of metrics.
     *
     * Generated from protobuf field <code>repeated .google.analytics.data.v1alpha.Row maximums = 9;</code>
     * @param \Google\Analytics\Data\V1alpha\Row[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setMaximums($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Analytics\Data\V1alpha\Row::class);
        $this->maximums = $arr;
        return $this;
    }
    /**
     * If requested, the minimum values of metrics.
     *
     * Generated from protobuf field <code>repeated .google.analytics.data.v1alpha.Row minimums = 10;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getMinimums()
    {
        return $this->minimums;
    }
    /**
     * If requested, the minimum values of metrics.
     *
     * Generated from protobuf field <code>repeated .google.analytics.data.v1alpha.Row minimums = 10;</code>
     * @param \Google\Analytics\Data\V1alpha\Row[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setMinimums($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Analytics\Data\V1alpha\Row::class);
        $this->minimums = $arr;
        return $this;
    }
    /**
     * The total number of rows in the query result, regardless of the number of
     * rows returned in the response. For example if a query returns 175 rows and
     * includes limit = 50 in the API request, the response will contain row_count
     * = 175 but only 50 rows.
     * To learn more about this pagination parameter, see
     * [Pagination](https://developers.google.com/analytics/devguides/reporting/data/v1/basics#pagination).
     *
     * Generated from protobuf field <code>int32 row_count = 12;</code>
     * @return int
     */
    public function getRowCount()
    {
        return $this->row_count;
    }
    /**
     * The total number of rows in the query result, regardless of the number of
     * rows returned in the response. For example if a query returns 175 rows and
     * includes limit = 50 in the API request, the response will contain row_count
     * = 175 but only 50 rows.
     * To learn more about this pagination parameter, see
     * [Pagination](https://developers.google.com/analytics/devguides/reporting/data/v1/basics#pagination).
     *
     * Generated from protobuf field <code>int32 row_count = 12;</code>
     * @param int $var
     * @return $this
     */
    public function setRowCount($var)
    {
        GPBUtil::checkInt32($var);
        $this->row_count = $var;
        return $this;
    }
    /**
     * Metadata for the report.
     *
     * Generated from protobuf field <code>.google.analytics.data.v1alpha.ResponseMetaData metadata = 6;</code>
     * @return \Google\Analytics\Data\V1alpha\ResponseMetaData|null
     */
    public function getMetadata()
    {
        return isset($this->metadata) ? $this->metadata : null;
    }
    public function hasMetadata()
    {
        return isset($this->metadata);
    }
    public function clearMetadata()
    {
        unset($this->metadata);
    }
    /**
     * Metadata for the report.
     *
     * Generated from protobuf field <code>.google.analytics.data.v1alpha.ResponseMetaData metadata = 6;</code>
     * @param \Google\Analytics\Data\V1alpha\ResponseMetaData $var
     * @return $this
     */
    public function setMetadata($var)
    {
        GPBUtil::checkMessage($var, \Google\Analytics\Data\V1alpha\ResponseMetaData::class);
        $this->metadata = $var;
        return $this;
    }
    /**
     * This Analytics Property's quota state including this request.
     *
     * Generated from protobuf field <code>.google.analytics.data.v1alpha.PropertyQuota property_quota = 7;</code>
     * @return \Google\Analytics\Data\V1alpha\PropertyQuota|null
     */
    public function getPropertyQuota()
    {
        return isset($this->property_quota) ? $this->property_quota : null;
    }
    public function hasPropertyQuota()
    {
        return isset($this->property_quota);
    }
    public function clearPropertyQuota()
    {
        unset($this->property_quota);
    }
    /**
     * This Analytics Property's quota state including this request.
     *
     * Generated from protobuf field <code>.google.analytics.data.v1alpha.PropertyQuota property_quota = 7;</code>
     * @param \Google\Analytics\Data\V1alpha\PropertyQuota $var
     * @return $this
     */
    public function setPropertyQuota($var)
    {
        GPBUtil::checkMessage($var, \Google\Analytics\Data\V1alpha\PropertyQuota::class);
        $this->property_quota = $var;
        return $this;
    }
}
