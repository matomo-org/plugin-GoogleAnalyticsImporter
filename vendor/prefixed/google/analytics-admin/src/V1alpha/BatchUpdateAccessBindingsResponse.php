<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/analytics/admin/v1alpha/analytics_admin.proto
namespace Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha;

use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBType;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\RepeatedField;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBUtil;
/**
 * Response message for BatchUpdateAccessBindings RPC.
 *
 * Generated from protobuf message <code>google.analytics.admin.v1alpha.BatchUpdateAccessBindingsResponse</code>
 */
class BatchUpdateAccessBindingsResponse extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\Message
{
    /**
     * The access bindings updated.
     *
     * Generated from protobuf field <code>repeated .google.analytics.admin.v1alpha.AccessBinding access_bindings = 1;</code>
     */
    private $access_bindings;
    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type array<\Google\Analytics\Admin\V1alpha\AccessBinding>|\Google\Protobuf\Internal\RepeatedField $access_bindings
     *           The access bindings updated.
     * }
     */
    public function __construct($data = NULL)
    {
        \Matomo\Dependencies\GoogleAnalyticsImporter\GPBMetadata\Google\Analytics\Admin\V1Alpha\AnalyticsAdmin::initOnce();
        parent::__construct($data);
    }
    /**
     * The access bindings updated.
     *
     * Generated from protobuf field <code>repeated .google.analytics.admin.v1alpha.AccessBinding access_bindings = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getAccessBindings()
    {
        return $this->access_bindings;
    }
    /**
     * The access bindings updated.
     *
     * Generated from protobuf field <code>repeated .google.analytics.admin.v1alpha.AccessBinding access_bindings = 1;</code>
     * @param array<\Google\Analytics\Admin\V1alpha\AccessBinding>|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setAccessBindings($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBType::MESSAGE, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\AccessBinding::class);
        $this->access_bindings = $arr;
        return $this;
    }
}