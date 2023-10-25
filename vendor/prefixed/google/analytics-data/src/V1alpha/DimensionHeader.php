<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/analytics/data/v1alpha/data.proto
namespace Google\Analytics\Data\V1alpha;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;
/**
 * Describes a dimension column in the report. Dimensions requested in a report
 * produce column entries within rows and DimensionHeaders. However, dimensions
 * used exclusively within filters or expressions do not produce columns in a
 * report; correspondingly, those dimensions do not produce headers.
 *
 * Generated from protobuf message <code>google.analytics.data.v1alpha.DimensionHeader</code>
 */
class DimensionHeader extends \Google\Protobuf\Internal\Message
{
    /**
     * The dimension's name.
     *
     * Generated from protobuf field <code>string name = 1;</code>
     */
    private $name = '';
    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $name
     *           The dimension's name.
     * }
     */
    public function __construct($data = NULL)
    {
        \GPBMetadata\Google\Analytics\Data\V1Alpha\Data::initOnce();
        parent::__construct($data);
    }
    /**
     * The dimension's name.
     *
     * Generated from protobuf field <code>string name = 1;</code>
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * The dimension's name.
     *
     * Generated from protobuf field <code>string name = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setName($var)
    {
        GPBUtil::checkString($var, True);
        $this->name = $var;
        return $this;
    }
}
