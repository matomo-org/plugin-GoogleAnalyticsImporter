<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/api/expr/v1alpha1/explain.proto
namespace Google\Api\Expr\V1alpha1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;
/**
 * Values of intermediate expressions produced when evaluating expression.
 * Deprecated, use `EvalState` instead.
 *
 * Generated from protobuf message <code>google.api.expr.v1alpha1.Explain</code>
 */
class Explain extends \Google\Protobuf\Internal\Message
{
    /**
     * All of the observed values.
     * The field value_index is an index in the values list.
     * Separating values from steps is needed to remove redundant values.
     *
     * Generated from protobuf field <code>repeated .google.api.expr.v1alpha1.Value values = 1;</code>
     */
    private $values;
    /**
     * List of steps.
     * Repeated evaluations of the same expression generate new ExprStep
     * instances. The order of such ExprStep instances matches the order of
     * elements returned by Comprehension.iter_range.
     *
     * Generated from protobuf field <code>repeated .google.api.expr.v1alpha1.Explain.ExprStep expr_steps = 2;</code>
     */
    private $expr_steps;
    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Api\Expr\V1alpha1\Value[]|\Google\Protobuf\Internal\RepeatedField $values
     *           All of the observed values.
     *           The field value_index is an index in the values list.
     *           Separating values from steps is needed to remove redundant values.
     *     @type \Google\Api\Expr\V1alpha1\Explain\ExprStep[]|\Google\Protobuf\Internal\RepeatedField $expr_steps
     *           List of steps.
     *           Repeated evaluations of the same expression generate new ExprStep
     *           instances. The order of such ExprStep instances matches the order of
     *           elements returned by Comprehension.iter_range.
     * }
     */
    public function __construct($data = NULL)
    {
        \GPBMetadata\Google\Api\Expr\V1Alpha1\Explain::initOnce();
        parent::__construct($data);
    }
    /**
     * All of the observed values.
     * The field value_index is an index in the values list.
     * Separating values from steps is needed to remove redundant values.
     *
     * Generated from protobuf field <code>repeated .google.api.expr.v1alpha1.Value values = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getValues()
    {
        return $this->values;
    }
    /**
     * All of the observed values.
     * The field value_index is an index in the values list.
     * Separating values from steps is needed to remove redundant values.
     *
     * Generated from protobuf field <code>repeated .google.api.expr.v1alpha1.Value values = 1;</code>
     * @param \Google\Api\Expr\V1alpha1\Value[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setValues($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Api\Expr\V1alpha1\Value::class);
        $this->values = $arr;
        return $this;
    }
    /**
     * List of steps.
     * Repeated evaluations of the same expression generate new ExprStep
     * instances. The order of such ExprStep instances matches the order of
     * elements returned by Comprehension.iter_range.
     *
     * Generated from protobuf field <code>repeated .google.api.expr.v1alpha1.Explain.ExprStep expr_steps = 2;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getExprSteps()
    {
        return $this->expr_steps;
    }
    /**
     * List of steps.
     * Repeated evaluations of the same expression generate new ExprStep
     * instances. The order of such ExprStep instances matches the order of
     * elements returned by Comprehension.iter_range.
     *
     * Generated from protobuf field <code>repeated .google.api.expr.v1alpha1.Explain.ExprStep expr_steps = 2;</code>
     * @param \Google\Api\Expr\V1alpha1\Explain\ExprStep[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setExprSteps($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Api\Expr\V1alpha1\Explain\ExprStep::class);
        $this->expr_steps = $arr;
        return $this;
    }
}
