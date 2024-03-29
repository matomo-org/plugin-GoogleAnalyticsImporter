<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/api/expr/v1alpha1/explain.proto
namespace Matomo\Dependencies\GoogleAnalyticsImporter\Google\Api\Expr\V1alpha1\Explain;

use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBType;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\RepeatedField;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBUtil;
/**
 * ID and value index of one step.
 *
 * Generated from protobuf message <code>google.api.expr.v1alpha1.Explain.ExprStep</code>
 */
class ExprStep extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\Message
{
    /**
     * ID of corresponding Expr node.
     *
     * Generated from protobuf field <code>int64 id = 1;</code>
     */
    private $id = 0;
    /**
     * Index of the value in the values list.
     *
     * Generated from protobuf field <code>int32 value_index = 2;</code>
     */
    private $value_index = 0;
    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int|string $id
     *           ID of corresponding Expr node.
     *     @type int $value_index
     *           Index of the value in the values list.
     * }
     */
    public function __construct($data = NULL)
    {
        \Matomo\Dependencies\GoogleAnalyticsImporter\GPBMetadata\Google\Api\Expr\V1Alpha1\Explain::initOnce();
        parent::__construct($data);
    }
    /**
     * ID of corresponding Expr node.
     *
     * Generated from protobuf field <code>int64 id = 1;</code>
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * ID of corresponding Expr node.
     *
     * Generated from protobuf field <code>int64 id = 1;</code>
     * @param int|string $var
     * @return $this
     */
    public function setId($var)
    {
        GPBUtil::checkInt64($var);
        $this->id = $var;
        return $this;
    }
    /**
     * Index of the value in the values list.
     *
     * Generated from protobuf field <code>int32 value_index = 2;</code>
     * @return int
     */
    public function getValueIndex()
    {
        return $this->value_index;
    }
    /**
     * Index of the value in the values list.
     *
     * Generated from protobuf field <code>int32 value_index = 2;</code>
     * @param int $var
     * @return $this
     */
    public function setValueIndex($var)
    {
        GPBUtil::checkInt32($var);
        $this->value_index = $var;
        return $this;
    }
}
// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ExprStep::class, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Api\Expr\V1alpha1\Explain_ExprStep::class);
