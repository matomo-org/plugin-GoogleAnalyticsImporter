<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/analytics/admin/v1alpha/analytics_admin.proto
namespace Google\Analytics\Admin\V1alpha;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;
/**
 * Request message for CreateProperty RPC.
 *
 * Generated from protobuf message <code>google.analytics.admin.v1alpha.CreatePropertyRequest</code>
 */
class CreatePropertyRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Required. The property to create.
     * Note: the supplied property must specify its parent.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1alpha.Property property = 1 [(.google.api.field_behavior) = REQUIRED];</code>
     */
    private $property = null;
    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Analytics\Admin\V1alpha\Property $property
     *           Required. The property to create.
     *           Note: the supplied property must specify its parent.
     * }
     */
    public function __construct($data = NULL)
    {
        \GPBMetadata\Google\Analytics\Admin\V1Alpha\AnalyticsAdmin::initOnce();
        parent::__construct($data);
    }
    /**
     * Required. The property to create.
     * Note: the supplied property must specify its parent.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1alpha.Property property = 1 [(.google.api.field_behavior) = REQUIRED];</code>
     * @return \Google\Analytics\Admin\V1alpha\Property|null
     */
    public function getProperty()
    {
        return $this->property;
    }
    public function hasProperty()
    {
        return isset($this->property);
    }
    public function clearProperty()
    {
        unset($this->property);
    }
    /**
     * Required. The property to create.
     * Note: the supplied property must specify its parent.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1alpha.Property property = 1 [(.google.api.field_behavior) = REQUIRED];</code>
     * @param \Google\Analytics\Admin\V1alpha\Property $var
     * @return $this
     */
    public function setProperty($var)
    {
        GPBUtil::checkMessage($var, \Google\Analytics\Admin\V1alpha\Property::class);
        $this->property = $var;
        return $this;
    }
}
