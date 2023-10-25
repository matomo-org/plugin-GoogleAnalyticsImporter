<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/protobuf/struct.proto
namespace Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;
/**
 * `Struct` represents a structured data value, consisting of fields
 * which map to dynamically typed values. In some languages, `Struct`
 * might be supported by a native representation. For example, in
 * scripting languages like JS a struct is represented as an
 * object. The details of that representation are described together
 * with the proto support for the language.
 * The JSON representation for `Struct` is JSON object.
 *
 * Generated from protobuf message <code>google.protobuf.Struct</code>
 */
class Struct extends \Google\Protobuf\Internal\Message
{
    /**
     * Unordered map of dynamically typed values.
     *
     * Generated from protobuf field <code>map<string, .google.protobuf.Value> fields = 1;</code>
     */
    private $fields;
    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type array|\Google\Protobuf\Internal\MapField $fields
     *           Unordered map of dynamically typed values.
     * }
     */
    public function __construct($data = NULL)
    {
        \Matomo\Dependencies\GoogleAnalyticsImporter\GPBMetadata\Google\Protobuf\Struct::initOnce();
        parent::__construct($data);
    }
    /**
     * Unordered map of dynamically typed values.
     *
     * Generated from protobuf field <code>map<string, .google.protobuf.Value> fields = 1;</code>
     * @return \Google\Protobuf\Internal\MapField
     */
    public function getFields()
    {
        return $this->fields;
    }
    /**
     * Unordered map of dynamically typed values.
     *
     * Generated from protobuf field <code>map<string, .google.protobuf.Value> fields = 1;</code>
     * @param array|\Google\Protobuf\Internal\MapField $var
     * @return $this
     */
    public function setFields($var)
    {
        $arr = GPBUtil::checkMapField($var, \Google\Protobuf\Internal\GPBType::STRING, \Google\Protobuf\Internal\GPBType::MESSAGE, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Value::class);
        $this->fields = $arr;
        return $this;
    }
}
