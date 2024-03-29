<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/analytics/admin/v1alpha/audience.proto
namespace Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\AudienceDimensionOrMetricFilter;

use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBType;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\RepeatedField;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBUtil;
/**
 * A filter for numeric or date values on a dimension or metric.
 *
 * Generated from protobuf message <code>google.analytics.admin.v1alpha.AudienceDimensionOrMetricFilter.NumericFilter</code>
 */
class NumericFilter extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\Message
{
    /**
     * Required. The operation applied to a numeric filter.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1alpha.AudienceDimensionOrMetricFilter.NumericFilter.Operation operation = 1 [(.google.api.field_behavior) = REQUIRED];</code>
     */
    private $operation = 0;
    /**
     * Required. The numeric or date value to match against.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1alpha.AudienceDimensionOrMetricFilter.NumericValue value = 2 [(.google.api.field_behavior) = REQUIRED];</code>
     */
    private $value = null;
    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int $operation
     *           Required. The operation applied to a numeric filter.
     *     @type \Google\Analytics\Admin\V1alpha\AudienceDimensionOrMetricFilter\NumericValue $value
     *           Required. The numeric or date value to match against.
     * }
     */
    public function __construct($data = NULL)
    {
        \Matomo\Dependencies\GoogleAnalyticsImporter\GPBMetadata\Google\Analytics\Admin\V1Alpha\Audience::initOnce();
        parent::__construct($data);
    }
    /**
     * Required. The operation applied to a numeric filter.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1alpha.AudienceDimensionOrMetricFilter.NumericFilter.Operation operation = 1 [(.google.api.field_behavior) = REQUIRED];</code>
     * @return int
     */
    public function getOperation()
    {
        return $this->operation;
    }
    /**
     * Required. The operation applied to a numeric filter.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1alpha.AudienceDimensionOrMetricFilter.NumericFilter.Operation operation = 1 [(.google.api.field_behavior) = REQUIRED];</code>
     * @param int $var
     * @return $this
     */
    public function setOperation($var)
    {
        GPBUtil::checkEnum($var, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\AudienceDimensionOrMetricFilter\NumericFilter\Operation::class);
        $this->operation = $var;
        return $this;
    }
    /**
     * Required. The numeric or date value to match against.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1alpha.AudienceDimensionOrMetricFilter.NumericValue value = 2 [(.google.api.field_behavior) = REQUIRED];</code>
     * @return \Google\Analytics\Admin\V1alpha\AudienceDimensionOrMetricFilter\NumericValue|null
     */
    public function getValue()
    {
        return $this->value;
    }
    public function hasValue()
    {
        return isset($this->value);
    }
    public function clearValue()
    {
        unset($this->value);
    }
    /**
     * Required. The numeric or date value to match against.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1alpha.AudienceDimensionOrMetricFilter.NumericValue value = 2 [(.google.api.field_behavior) = REQUIRED];</code>
     * @param \Google\Analytics\Admin\V1alpha\AudienceDimensionOrMetricFilter\NumericValue $var
     * @return $this
     */
    public function setValue($var)
    {
        GPBUtil::checkMessage($var, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\AudienceDimensionOrMetricFilter\NumericValue::class);
        $this->value = $var;
        return $this;
    }
}
// Adding a class alias for backwards compatibility with the previous class name.
class_alias(NumericFilter::class, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\AudienceDimensionOrMetricFilter_NumericFilter::class);
