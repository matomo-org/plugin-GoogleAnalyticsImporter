<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/rpc/error_details.proto
namespace Matomo\Dependencies\GoogleAnalyticsImporter\Google\Rpc;

use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBType;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\RepeatedField;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBUtil;
/**
 * Describes additional debugging info.
 *
 * Generated from protobuf message <code>google.rpc.DebugInfo</code>
 */
class DebugInfo extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\Message
{
    /**
     * The stack trace entries indicating where the error occurred.
     *
     * Generated from protobuf field <code>repeated string stack_entries = 1;</code>
     */
    private $stack_entries;
    /**
     * Additional debugging information provided by the server.
     *
     * Generated from protobuf field <code>string detail = 2;</code>
     */
    protected $detail = '';
    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string[]|\Google\Protobuf\Internal\RepeatedField $stack_entries
     *           The stack trace entries indicating where the error occurred.
     *     @type string $detail
     *           Additional debugging information provided by the server.
     * }
     */
    public function __construct($data = NULL)
    {
        \Matomo\Dependencies\GoogleAnalyticsImporter\GPBMetadata\Google\Rpc\ErrorDetails::initOnce();
        parent::__construct($data);
    }
    /**
     * The stack trace entries indicating where the error occurred.
     *
     * Generated from protobuf field <code>repeated string stack_entries = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getStackEntries()
    {
        return $this->stack_entries;
    }
    /**
     * The stack trace entries indicating where the error occurred.
     *
     * Generated from protobuf field <code>repeated string stack_entries = 1;</code>
     * @param string[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setStackEntries($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBType::STRING);
        $this->stack_entries = $arr;
        return $this;
    }
    /**
     * Additional debugging information provided by the server.
     *
     * Generated from protobuf field <code>string detail = 2;</code>
     * @return string
     */
    public function getDetail()
    {
        return $this->detail;
    }
    /**
     * Additional debugging information provided by the server.
     *
     * Generated from protobuf field <code>string detail = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setDetail($var)
    {
        GPBUtil::checkString($var, True);
        $this->detail = $var;
        return $this;
    }
}