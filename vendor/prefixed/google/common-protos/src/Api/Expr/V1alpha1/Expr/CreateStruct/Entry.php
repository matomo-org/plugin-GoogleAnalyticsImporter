<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/api/expr/v1alpha1/syntax.proto
namespace Matomo\Dependencies\GoogleAnalyticsImporter\Google\Api\Expr\V1alpha1\Expr\CreateStruct;

use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBType;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\RepeatedField;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBUtil;
/**
 * Represents an entry.
 *
 * Generated from protobuf message <code>google.api.expr.v1alpha1.Expr.CreateStruct.Entry</code>
 */
class Entry extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\Message
{
    /**
     * Required. An id assigned to this node by the parser which is unique
     * in a given expression tree. This is used to associate type
     * information and other attributes to the node.
     *
     * Generated from protobuf field <code>int64 id = 1;</code>
     */
    private $id = 0;
    /**
     * Required. The value assigned to the key.
     *
     * Generated from protobuf field <code>.google.api.expr.v1alpha1.Expr value = 4;</code>
     */
    private $value = null;
    protected $key_kind;
    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int|string $id
     *           Required. An id assigned to this node by the parser which is unique
     *           in a given expression tree. This is used to associate type
     *           information and other attributes to the node.
     *     @type string $field_key
     *           The field key for a message creator statement.
     *     @type \Google\Api\Expr\V1alpha1\Expr $map_key
     *           The key expression for a map creation statement.
     *     @type \Google\Api\Expr\V1alpha1\Expr $value
     *           Required. The value assigned to the key.
     * }
     */
    public function __construct($data = NULL)
    {
        \Matomo\Dependencies\GoogleAnalyticsImporter\GPBMetadata\Google\Api\Expr\V1Alpha1\Syntax::initOnce();
        parent::__construct($data);
    }
    /**
     * Required. An id assigned to this node by the parser which is unique
     * in a given expression tree. This is used to associate type
     * information and other attributes to the node.
     *
     * Generated from protobuf field <code>int64 id = 1;</code>
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * Required. An id assigned to this node by the parser which is unique
     * in a given expression tree. This is used to associate type
     * information and other attributes to the node.
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
     * The field key for a message creator statement.
     *
     * Generated from protobuf field <code>string field_key = 2;</code>
     * @return string
     */
    public function getFieldKey()
    {
        return $this->readOneof(2);
    }
    /**
     * The field key for a message creator statement.
     *
     * Generated from protobuf field <code>string field_key = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setFieldKey($var)
    {
        GPBUtil::checkString($var, True);
        $this->writeOneof(2, $var);
        return $this;
    }
    /**
     * The key expression for a map creation statement.
     *
     * Generated from protobuf field <code>.google.api.expr.v1alpha1.Expr map_key = 3;</code>
     * @return \Google\Api\Expr\V1alpha1\Expr
     */
    public function getMapKey()
    {
        return $this->readOneof(3);
    }
    /**
     * The key expression for a map creation statement.
     *
     * Generated from protobuf field <code>.google.api.expr.v1alpha1.Expr map_key = 3;</code>
     * @param \Google\Api\Expr\V1alpha1\Expr $var
     * @return $this
     */
    public function setMapKey($var)
    {
        GPBUtil::checkMessage($var, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Api\Expr\V1alpha1\Expr::class);
        $this->writeOneof(3, $var);
        return $this;
    }
    /**
     * Required. The value assigned to the key.
     *
     * Generated from protobuf field <code>.google.api.expr.v1alpha1.Expr value = 4;</code>
     * @return \Google\Api\Expr\V1alpha1\Expr
     */
    public function getValue()
    {
        return $this->value;
    }
    /**
     * Required. The value assigned to the key.
     *
     * Generated from protobuf field <code>.google.api.expr.v1alpha1.Expr value = 4;</code>
     * @param \Google\Api\Expr\V1alpha1\Expr $var
     * @return $this
     */
    public function setValue($var)
    {
        GPBUtil::checkMessage($var, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Api\Expr\V1alpha1\Expr::class);
        $this->value = $var;
        return $this;
    }
    /**
     * @return string
     */
    public function getKeyKind()
    {
        return $this->whichOneof("key_kind");
    }
}
// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Entry::class, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Api\Expr\V1alpha1\Expr_CreateStruct_Entry::class);
