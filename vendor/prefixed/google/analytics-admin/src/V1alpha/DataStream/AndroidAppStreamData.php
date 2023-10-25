<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/analytics/admin/v1alpha/resources.proto
namespace Google\Analytics\Admin\V1alpha\DataStream;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;
/**
 * Data specific to Android app streams.
 *
 * Generated from protobuf message <code>google.analytics.admin.v1alpha.DataStream.AndroidAppStreamData</code>
 */
class AndroidAppStreamData extends \Google\Protobuf\Internal\Message
{
    /**
     * Output only. ID of the corresponding Android app in Firebase, if any.
     * This ID can change if the Android app is deleted and recreated.
     *
     * Generated from protobuf field <code>string firebase_app_id = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    private $firebase_app_id = '';
    /**
     * Immutable. The package name for the app being measured.
     * Example: "com.example.myandroidapp"
     *
     * Generated from protobuf field <code>string package_name = 2 [(.google.api.field_behavior) = IMMUTABLE];</code>
     */
    private $package_name = '';
    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $firebase_app_id
     *           Output only. ID of the corresponding Android app in Firebase, if any.
     *           This ID can change if the Android app is deleted and recreated.
     *     @type string $package_name
     *           Immutable. The package name for the app being measured.
     *           Example: "com.example.myandroidapp"
     * }
     */
    public function __construct($data = NULL)
    {
        \GPBMetadata\Google\Analytics\Admin\V1Alpha\Resources::initOnce();
        parent::__construct($data);
    }
    /**
     * Output only. ID of the corresponding Android app in Firebase, if any.
     * This ID can change if the Android app is deleted and recreated.
     *
     * Generated from protobuf field <code>string firebase_app_id = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return string
     */
    public function getFirebaseAppId()
    {
        return $this->firebase_app_id;
    }
    /**
     * Output only. ID of the corresponding Android app in Firebase, if any.
     * This ID can change if the Android app is deleted and recreated.
     *
     * Generated from protobuf field <code>string firebase_app_id = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param string $var
     * @return $this
     */
    public function setFirebaseAppId($var)
    {
        GPBUtil::checkString($var, True);
        $this->firebase_app_id = $var;
        return $this;
    }
    /**
     * Immutable. The package name for the app being measured.
     * Example: "com.example.myandroidapp"
     *
     * Generated from protobuf field <code>string package_name = 2 [(.google.api.field_behavior) = IMMUTABLE];</code>
     * @return string
     */
    public function getPackageName()
    {
        return $this->package_name;
    }
    /**
     * Immutable. The package name for the app being measured.
     * Example: "com.example.myandroidapp"
     *
     * Generated from protobuf field <code>string package_name = 2 [(.google.api.field_behavior) = IMMUTABLE];</code>
     * @param string $var
     * @return $this
     */
    public function setPackageName($var)
    {
        GPBUtil::checkString($var, True);
        $this->package_name = $var;
        return $this;
    }
}
// Adding a class alias for backwards compatibility with the previous class name.
class_alias(\Google\Analytics\Admin\V1alpha\DataStream\AndroidAppStreamData::class, \Google\Analytics\Admin\V1alpha\DataStream_AndroidAppStreamData::class);
