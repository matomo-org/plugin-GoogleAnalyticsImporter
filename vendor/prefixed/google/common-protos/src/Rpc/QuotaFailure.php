<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/rpc/error_details.proto
namespace Matomo\Dependencies\GoogleAnalyticsImporter\Google\Rpc;

use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBType;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\RepeatedField;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBUtil;
/**
 * Describes how a quota check failed.
 * For example if a daily limit was exceeded for the calling project,
 * a service could respond with a QuotaFailure detail containing the project
 * id and the description of the quota limit that was exceeded.  If the
 * calling project hasn't enabled the service in the developer console, then
 * a service could respond with the project id and set `service_disabled`
 * to true.
 * Also see RetryInfo and Help types for other details about handling a
 * quota failure.
 *
 * Generated from protobuf message <code>google.rpc.QuotaFailure</code>
 */
class QuotaFailure extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\Message
{
    /**
     * Describes all quota violations.
     *
     * Generated from protobuf field <code>repeated .google.rpc.QuotaFailure.Violation violations = 1;</code>
     */
    private $violations;
    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Rpc\QuotaFailure\Violation[]|\Google\Protobuf\Internal\RepeatedField $violations
     *           Describes all quota violations.
     * }
     */
    public function __construct($data = NULL)
    {
        \Matomo\Dependencies\GoogleAnalyticsImporter\GPBMetadata\Google\Rpc\ErrorDetails::initOnce();
        parent::__construct($data);
    }
    /**
     * Describes all quota violations.
     *
     * Generated from protobuf field <code>repeated .google.rpc.QuotaFailure.Violation violations = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getViolations()
    {
        return $this->violations;
    }
    /**
     * Describes all quota violations.
     *
     * Generated from protobuf field <code>repeated .google.rpc.QuotaFailure.Violation violations = 1;</code>
     * @param \Google\Rpc\QuotaFailure\Violation[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setViolations($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBType::MESSAGE, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Rpc\QuotaFailure\Violation::class);
        $this->violations = $arr;
        return $this;
    }
}