<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/analytics/data/v1alpha/data.proto
namespace Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1alpha;

use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBType;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\RepeatedField;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBUtil;
/**
 * The value of a metric.
 *
 * Generated from protobuf message <code>google.analytics.data.v1alpha.MetricValue</code>
 */
class MetricValue extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\Message
{
    protected $one_value;
    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $value
     *           Measurement value. See MetricHeader for type.
     * }
     */
    public function __construct($data = NULL)
    {
        \Matomo\Dependencies\GoogleAnalyticsImporter\GPBMetadata\Google\Analytics\Data\V1Alpha\Data::initOnce();
        parent::__construct($data);
    }
    /**
     * Measurement value. See MetricHeader for type.
     *
     * Generated from protobuf field <code>string value = 4;</code>
     * @return string
     */
    public function getValue()
    {
        return $this->readOneof(4);
    }
    public function hasValue()
    {
        return $this->hasOneof(4);
    }
    /**
     * Measurement value. See MetricHeader for type.
     *
     * Generated from protobuf field <code>string value = 4;</code>
     * @param string $var
     * @return $this
     */
    public function setValue($var)
    {
        GPBUtil::checkString($var, True);
        $this->writeOneof(4, $var);
        return $this;
    }
    /**
     * @return string
     */
    public function getOneValue()
    {
        return $this->whichOneof("one_value");
    }
}