<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/api/expr/v1alpha1/syntax.proto
namespace Google\Api\Expr\V1alpha1\Expr;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;
/**
 * A list creation expression.
 * Lists may either be homogenous, e.g. `[1, 2, 3]`, or heterogenous, e.g.
 * `dyn([1, 'hello', 2.0])`
 *
 * Generated from protobuf message <code>google.api.expr.v1alpha1.Expr.CreateList</code>
 */
class CreateList extends \Google\Protobuf\Internal\Message
{
    /**
     * The elements part of the list.
     *
     * Generated from protobuf field <code>repeated .google.api.expr.v1alpha1.Expr elements = 1;</code>
     */
    private $elements;
    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Api\Expr\V1alpha1\Expr[]|\Google\Protobuf\Internal\RepeatedField $elements
     *           The elements part of the list.
     * }
     */
    public function __construct($data = NULL)
    {
        \GPBMetadata\Google\Api\Expr\V1Alpha1\Syntax::initOnce();
        parent::__construct($data);
    }
    /**
     * The elements part of the list.
     *
     * Generated from protobuf field <code>repeated .google.api.expr.v1alpha1.Expr elements = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getElements()
    {
        return $this->elements;
    }
    /**
     * The elements part of the list.
     *
     * Generated from protobuf field <code>repeated .google.api.expr.v1alpha1.Expr elements = 1;</code>
     * @param \Google\Api\Expr\V1alpha1\Expr[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setElements($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Api\Expr\V1alpha1\Expr::class);
        $this->elements = $arr;
        return $this;
    }
}
// Adding a class alias for backwards compatibility with the previous class name.
class_alias(\Google\Api\Expr\V1alpha1\Expr\CreateList::class, \Google\Api\Expr\V1alpha1\Expr_CreateList::class);
