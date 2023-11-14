<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/api/expr/v1alpha1/eval.proto
namespace Matomo\Dependencies\GoogleAnalyticsImporter\Google\Api\Expr\V1alpha1;

use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBType;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\RepeatedField;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBUtil;
/**
 * A set of expressions for which the value is unknown.
 * The unknowns included depend on the context. See `ExprValue.unknown`.
 *
 * Generated from protobuf message <code>google.api.expr.v1alpha1.UnknownSet</code>
 */
class UnknownSet extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\Message
{
    /**
     * The ids of the expressions with unknown values.
     *
     * Generated from protobuf field <code>repeated int64 exprs = 1;</code>
     */
    private $exprs;
    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int[]|string[]|\Google\Protobuf\Internal\RepeatedField $exprs
     *           The ids of the expressions with unknown values.
     * }
     */
    public function __construct($data = NULL)
    {
        \Matomo\Dependencies\GoogleAnalyticsImporter\GPBMetadata\Google\Api\Expr\V1Alpha1\PBEval::initOnce();
        parent::__construct($data);
    }
    /**
     * The ids of the expressions with unknown values.
     *
     * Generated from protobuf field <code>repeated int64 exprs = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getExprs()
    {
        return $this->exprs;
    }
    /**
     * The ids of the expressions with unknown values.
     *
     * Generated from protobuf field <code>repeated int64 exprs = 1;</code>
     * @param int[]|string[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setExprs($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBType::INT64);
        $this->exprs = $arr;
        return $this;
    }
}