<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/analytics/data/v1beta/data.proto
namespace Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\OrderBy\PivotOrderBy;

use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBType;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\RepeatedField;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBUtil;
/**
 * A pair of dimension names and values. Rows with this dimension pivot pair
 * are ordered by the metric's value.
 * For example if pivots = {{"browser", "Chrome"}} and
 * metric_name = "Sessions",
 * then the rows will be sorted based on Sessions in Chrome.
 *     ---------|----------|----------------|----------|----------------
 *              |  Chrome  |    Chrome      |  Safari  |     Safari
 *     ---------|----------|----------------|----------|----------------
 *      Country | Sessions | Pages/Sessions | Sessions | Pages/Sessions
 *     ---------|----------|----------------|----------|----------------
 *         US   |    2     |       2        |     3    |        1
 *     ---------|----------|----------------|----------|----------------
 *       Canada |    3     |       1        |     4    |        1
 *     ---------|----------|----------------|----------|----------------
 *
 * Generated from protobuf message <code>google.analytics.data.v1beta.OrderBy.PivotOrderBy.PivotSelection</code>
 */
class PivotSelection extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\Message
{
    /**
     * Must be a dimension name from the request.
     *
     * Generated from protobuf field <code>string dimension_name = 1;</code>
     */
    private $dimension_name = '';
    /**
     * Order by only when the named dimension is this value.
     *
     * Generated from protobuf field <code>string dimension_value = 2;</code>
     */
    private $dimension_value = '';
    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $dimension_name
     *           Must be a dimension name from the request.
     *     @type string $dimension_value
     *           Order by only when the named dimension is this value.
     * }
     */
    public function __construct($data = NULL)
    {
        \Matomo\Dependencies\GoogleAnalyticsImporter\GPBMetadata\Google\Analytics\Data\V1Beta\Data::initOnce();
        parent::__construct($data);
    }
    /**
     * Must be a dimension name from the request.
     *
     * Generated from protobuf field <code>string dimension_name = 1;</code>
     * @return string
     */
    public function getDimensionName()
    {
        return $this->dimension_name;
    }
    /**
     * Must be a dimension name from the request.
     *
     * Generated from protobuf field <code>string dimension_name = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setDimensionName($var)
    {
        GPBUtil::checkString($var, True);
        $this->dimension_name = $var;
        return $this;
    }
    /**
     * Order by only when the named dimension is this value.
     *
     * Generated from protobuf field <code>string dimension_value = 2;</code>
     * @return string
     */
    public function getDimensionValue()
    {
        return $this->dimension_value;
    }
    /**
     * Order by only when the named dimension is this value.
     *
     * Generated from protobuf field <code>string dimension_value = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setDimensionValue($var)
    {
        GPBUtil::checkString($var, True);
        $this->dimension_value = $var;
        return $this;
    }
}