<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/protobuf/descriptor.proto
namespace Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal;

use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBType;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBWire;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\RepeatedField;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\InputStream;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBUtil;
/**
 * Describes a service.
 *
 * Generated from protobuf message <code>google.protobuf.ServiceDescriptorProto</code>
 */
class ServiceDescriptorProto extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>optional string name = 1;</code>
     */
    protected $name = null;
    /**
     * Generated from protobuf field <code>repeated .google.protobuf.MethodDescriptorProto method = 2;</code>
     */
    private $method;
    /**
     * Generated from protobuf field <code>optional .google.protobuf.ServiceOptions options = 3;</code>
     */
    protected $options = null;
    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $name
     *     @type array<\Google\Protobuf\Internal\MethodDescriptorProto>|\Google\Protobuf\Internal\RepeatedField $method
     *     @type \Google\Protobuf\Internal\ServiceOptions $options
     * }
     */
    public function __construct($data = NULL)
    {
        \Matomo\Dependencies\GoogleAnalyticsImporter\GPBMetadata\Google\Protobuf\Internal\Descriptor::initOnce();
        parent::__construct($data);
    }
    /**
     * Generated from protobuf field <code>optional string name = 1;</code>
     * @return string
     */
    public function getName()
    {
        return isset($this->name) ? $this->name : '';
    }
    public function hasName()
    {
        return isset($this->name);
    }
    public function clearName()
    {
        unset($this->name);
    }
    /**
     * Generated from protobuf field <code>optional string name = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setName($var)
    {
        GPBUtil::checkString($var, True);
        $this->name = $var;
        return $this;
    }
    /**
     * Generated from protobuf field <code>repeated .google.protobuf.MethodDescriptorProto method = 2;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getMethod()
    {
        return $this->method;
    }
    /**
     * Generated from protobuf field <code>repeated .google.protobuf.MethodDescriptorProto method = 2;</code>
     * @param array<\Google\Protobuf\Internal\MethodDescriptorProto>|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setMethod($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBType::MESSAGE, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\MethodDescriptorProto::class);
        $this->method = $arr;
        return $this;
    }
    /**
     * Generated from protobuf field <code>optional .google.protobuf.ServiceOptions options = 3;</code>
     * @return \Google\Protobuf\Internal\ServiceOptions|null
     */
    public function getOptions()
    {
        return $this->options;
    }
    public function hasOptions()
    {
        return isset($this->options);
    }
    public function clearOptions()
    {
        unset($this->options);
    }
    /**
     * Generated from protobuf field <code>optional .google.protobuf.ServiceOptions options = 3;</code>
     * @param \Google\Protobuf\Internal\ServiceOptions $var
     * @return $this
     */
    public function setOptions($var)
    {
        GPBUtil::checkMessage($var, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\ServiceOptions::class);
        $this->options = $var;
        return $this;
    }
}