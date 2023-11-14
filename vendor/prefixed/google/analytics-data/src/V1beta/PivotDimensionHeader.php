<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/analytics/data/v1beta/data.proto
namespace Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta;

use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBType;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\RepeatedField;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBUtil;
/**
 * Summarizes dimension values from a row for this pivot.
 *
 * Generated from protobuf message <code>google.analytics.data.v1beta.PivotDimensionHeader</code>
 */
class PivotDimensionHeader extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\Message
{
    /**
     * Values of multiple dimensions in a pivot.
     *
     * Generated from protobuf field <code>repeated .google.analytics.data.v1beta.DimensionValue dimension_values = 1;</code>
     */
    private $dimension_values;
    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type array<\Google\Analytics\Data\V1beta\DimensionValue>|\Google\Protobuf\Internal\RepeatedField $dimension_values
     *           Values of multiple dimensions in a pivot.
     * }
     */
    public function __construct($data = NULL)
    {
        \Matomo\Dependencies\GoogleAnalyticsImporter\GPBMetadata\Google\Analytics\Data\V1Beta\Data::initOnce();
        parent::__construct($data);
    }
    /**
     * Values of multiple dimensions in a pivot.
     *
     * Generated from protobuf field <code>repeated .google.analytics.data.v1beta.DimensionValue dimension_values = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getDimensionValues()
    {
        return $this->dimension_values;
    }
    /**
     * Values of multiple dimensions in a pivot.
     *
     * Generated from protobuf field <code>repeated .google.analytics.data.v1beta.DimensionValue dimension_values = 1;</code>
     * @param array<\Google\Analytics\Data\V1beta\DimensionValue>|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setDimensionValues($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBType::MESSAGE, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\DimensionValue::class);
        $this->dimension_values = $arr;
        return $this;
    }
}