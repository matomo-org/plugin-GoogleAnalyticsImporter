<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/api/expr/v1beta1/value.proto
namespace Matomo\Dependencies\GoogleAnalyticsImporter\Google\Api\Expr\V1beta1\MapValue;

use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBType;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\RepeatedField;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBUtil;
/**
 * An entry in the map.
 *
 * Generated from protobuf message <code>google.api.expr.v1beta1.MapValue.Entry</code>
 */
class Entry extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\Message
{
    /**
     * The key.
     * Must be unique with in the map.
     * Currently only boolean, int, uint, and string values can be keys.
     *
     * Generated from protobuf field <code>.google.api.expr.v1beta1.Value key = 1;</code>
     */
    private $key = null;
    /**
     * The value.
     *
     * Generated from protobuf field <code>.google.api.expr.v1beta1.Value value = 2;</code>
     */
    private $value = null;
    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Api\Expr\V1beta1\Value $key
     *           The key.
     *           Must be unique with in the map.
     *           Currently only boolean, int, uint, and string values can be keys.
     *     @type \Google\Api\Expr\V1beta1\Value $value
     *           The value.
     * }
     */
    public function __construct($data = NULL)
    {
        \Matomo\Dependencies\GoogleAnalyticsImporter\GPBMetadata\Google\Api\Expr\V1Beta1\Value::initOnce();
        parent::__construct($data);
    }
    /**
     * The key.
     * Must be unique with in the map.
     * Currently only boolean, int, uint, and string values can be keys.
     *
     * Generated from protobuf field <code>.google.api.expr.v1beta1.Value key = 1;</code>
     * @return \Google\Api\Expr\V1beta1\Value
     */
    public function getKey()
    {
        return $this->key;
    }
    /**
     * The key.
     * Must be unique with in the map.
     * Currently only boolean, int, uint, and string values can be keys.
     *
     * Generated from protobuf field <code>.google.api.expr.v1beta1.Value key = 1;</code>
     * @param \Google\Api\Expr\V1beta1\Value $var
     * @return $this
     */
    public function setKey($var)
    {
        GPBUtil::checkMessage($var, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Api\Expr\V1beta1\Value::class);
        $this->key = $var;
        return $this;
    }
    /**
     * The value.
     *
     * Generated from protobuf field <code>.google.api.expr.v1beta1.Value value = 2;</code>
     * @return \Google\Api\Expr\V1beta1\Value
     */
    public function getValue()
    {
        return $this->value;
    }
    /**
     * The value.
     *
     * Generated from protobuf field <code>.google.api.expr.v1beta1.Value value = 2;</code>
     * @param \Google\Api\Expr\V1beta1\Value $var
     * @return $this
     */
    public function setValue($var)
    {
        GPBUtil::checkMessage($var, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Api\Expr\V1beta1\Value::class);
        $this->value = $var;
        return $this;
    }
}
// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Entry::class, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Api\Expr\V1beta1\MapValue_Entry::class);
