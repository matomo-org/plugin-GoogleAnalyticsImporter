<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/protobuf/wrappers.proto
namespace Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf;

use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBType;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\RepeatedField;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBUtil;
/**
 * Wrapper message for `bytes`.
 * The JSON representation for `BytesValue` is JSON string.
 *
 * Generated from protobuf message <code>google.protobuf.BytesValue</code>
 */
class BytesValue extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\Message
{
    /**
     * The bytes value.
     *
     * Generated from protobuf field <code>bytes value = 1;</code>
     */
    protected $value = '';
    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $value
     *           The bytes value.
     * }
     */
    public function __construct($data = NULL)
    {
        \Matomo\Dependencies\GoogleAnalyticsImporter\GPBMetadata\Google\Protobuf\Wrappers::initOnce();
        parent::__construct($data);
    }
    /**
     * The bytes value.
     *
     * Generated from protobuf field <code>bytes value = 1;</code>
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
    /**
     * The bytes value.
     *
     * Generated from protobuf field <code>bytes value = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setValue($var)
    {
        GPBUtil::checkString($var, False);
        $this->value = $var;
        return $this;
    }
}