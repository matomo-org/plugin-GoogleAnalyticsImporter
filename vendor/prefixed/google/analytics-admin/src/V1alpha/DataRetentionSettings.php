<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/analytics/admin/v1alpha/resources.proto
namespace Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha;

use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBType;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\RepeatedField;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBUtil;
/**
 * Settings values for data retention. This is a singleton resource.
 *
 * Generated from protobuf message <code>google.analytics.admin.v1alpha.DataRetentionSettings</code>
 */
class DataRetentionSettings extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\Message
{
    /**
     * Output only. Resource name for this DataRetentionSetting resource.
     * Format: properties/{property}/dataRetentionSettings
     *
     * Generated from protobuf field <code>string name = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    private $name = '';
    /**
     * The length of time that event-level data is retained.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1alpha.DataRetentionSettings.RetentionDuration event_data_retention = 2;</code>
     */
    private $event_data_retention = 0;
    /**
     * If true, reset the retention period for the user identifier with every
     * event from that user.
     *
     * Generated from protobuf field <code>bool reset_user_data_on_new_activity = 3;</code>
     */
    private $reset_user_data_on_new_activity = \false;
    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $name
     *           Output only. Resource name for this DataRetentionSetting resource.
     *           Format: properties/{property}/dataRetentionSettings
     *     @type int $event_data_retention
     *           The length of time that event-level data is retained.
     *     @type bool $reset_user_data_on_new_activity
     *           If true, reset the retention period for the user identifier with every
     *           event from that user.
     * }
     */
    public function __construct($data = NULL)
    {
        \Matomo\Dependencies\GoogleAnalyticsImporter\GPBMetadata\Google\Analytics\Admin\V1Alpha\Resources::initOnce();
        parent::__construct($data);
    }
    /**
     * Output only. Resource name for this DataRetentionSetting resource.
     * Format: properties/{property}/dataRetentionSettings
     *
     * Generated from protobuf field <code>string name = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * Output only. Resource name for this DataRetentionSetting resource.
     * Format: properties/{property}/dataRetentionSettings
     *
     * Generated from protobuf field <code>string name = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
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
     * The length of time that event-level data is retained.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1alpha.DataRetentionSettings.RetentionDuration event_data_retention = 2;</code>
     * @return int
     */
    public function getEventDataRetention()
    {
        return $this->event_data_retention;
    }
    /**
     * The length of time that event-level data is retained.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1alpha.DataRetentionSettings.RetentionDuration event_data_retention = 2;</code>
     * @param int $var
     * @return $this
     */
    public function setEventDataRetention($var)
    {
        GPBUtil::checkEnum($var, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\DataRetentionSettings\RetentionDuration::class);
        $this->event_data_retention = $var;
        return $this;
    }
    /**
     * If true, reset the retention period for the user identifier with every
     * event from that user.
     *
     * Generated from protobuf field <code>bool reset_user_data_on_new_activity = 3;</code>
     * @return bool
     */
    public function getResetUserDataOnNewActivity()
    {
        return $this->reset_user_data_on_new_activity;
    }
    /**
     * If true, reset the retention period for the user identifier with every
     * event from that user.
     *
     * Generated from protobuf field <code>bool reset_user_data_on_new_activity = 3;</code>
     * @param bool $var
     * @return $this
     */
    public function setResetUserDataOnNewActivity($var)
    {
        GPBUtil::checkBool($var);
        $this->reset_user_data_on_new_activity = $var;
        return $this;
    }
}
