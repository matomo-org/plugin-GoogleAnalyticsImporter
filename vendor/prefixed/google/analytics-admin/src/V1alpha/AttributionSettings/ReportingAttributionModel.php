<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/analytics/admin/v1alpha/resources.proto
namespace Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\AttributionSettings;

use UnexpectedValueException;
/**
 * The reporting attribution model used to calculate conversion credit in this
 * property's reports.
 *
 * Protobuf type <code>google.analytics.admin.v1alpha.AttributionSettings.ReportingAttributionModel</code>
 */
class ReportingAttributionModel
{
    /**
     * Reporting attribution model unspecified.
     *
     * Generated from protobuf enum <code>REPORTING_ATTRIBUTION_MODEL_UNSPECIFIED = 0;</code>
     */
    const REPORTING_ATTRIBUTION_MODEL_UNSPECIFIED = 0;
    /**
     * Data-driven attribution distributes credit for the conversion based on
     * data for each conversion event. Each Data-driven model is specific to
     * each advertiser and each conversion event.
     *
     * Generated from protobuf enum <code>CROSS_CHANNEL_DATA_DRIVEN = 1;</code>
     */
    const CROSS_CHANNEL_DATA_DRIVEN = 1;
    /**
     * Ignores direct traffic and attributes 100% of the conversion value to the
     * last channel that the customer clicked through (or engaged view through
     * for YouTube) before converting.
     *
     * Generated from protobuf enum <code>CROSS_CHANNEL_LAST_CLICK = 2;</code>
     */
    const CROSS_CHANNEL_LAST_CLICK = 2;
    /**
     * Gives all credit for the conversion to the first channel that a customer
     * clicked (or engaged view through for YouTube) before converting.
     *
     * Generated from protobuf enum <code>CROSS_CHANNEL_FIRST_CLICK = 3;</code>
     */
    const CROSS_CHANNEL_FIRST_CLICK = 3;
    /**
     * Distributes the credit for the conversion equally across all the channels
     * a customer clicked (or engaged view through for YouTube) before
     * converting.
     *
     * Generated from protobuf enum <code>CROSS_CHANNEL_LINEAR = 4;</code>
     */
    const CROSS_CHANNEL_LINEAR = 4;
    /**
     * Attributes 40% credit to the first and last interaction, and the
     * remaining 20% credit is distributed evenly to the middle interactions.
     *
     * Generated from protobuf enum <code>CROSS_CHANNEL_POSITION_BASED = 5;</code>
     */
    const CROSS_CHANNEL_POSITION_BASED = 5;
    /**
     * Gives more credit to the touchpoints that happened closer in time to
     * the conversion.
     *
     * Generated from protobuf enum <code>CROSS_CHANNEL_TIME_DECAY = 6;</code>
     */
    const CROSS_CHANNEL_TIME_DECAY = 6;
    /**
     * Attributes 100% of the conversion value to the last Google Ads channel
     * that the customer clicked through before converting.
     *
     * Generated from protobuf enum <code>ADS_PREFERRED_LAST_CLICK = 7;</code>
     */
    const ADS_PREFERRED_LAST_CLICK = 7;
    private static $valueToName = [self::REPORTING_ATTRIBUTION_MODEL_UNSPECIFIED => 'REPORTING_ATTRIBUTION_MODEL_UNSPECIFIED', self::CROSS_CHANNEL_DATA_DRIVEN => 'CROSS_CHANNEL_DATA_DRIVEN', self::CROSS_CHANNEL_LAST_CLICK => 'CROSS_CHANNEL_LAST_CLICK', self::CROSS_CHANNEL_FIRST_CLICK => 'CROSS_CHANNEL_FIRST_CLICK', self::CROSS_CHANNEL_LINEAR => 'CROSS_CHANNEL_LINEAR', self::CROSS_CHANNEL_POSITION_BASED => 'CROSS_CHANNEL_POSITION_BASED', self::CROSS_CHANNEL_TIME_DECAY => 'CROSS_CHANNEL_TIME_DECAY', self::ADS_PREFERRED_LAST_CLICK => 'ADS_PREFERRED_LAST_CLICK'];
    public static function name($value)
    {
        if (!isset(self::$valueToName[$value])) {
            throw new UnexpectedValueException(sprintf('Enum %s has no name defined for value %s', __CLASS__, $value));
        }
        return self::$valueToName[$value];
    }
    public static function value($name)
    {
        $const = __CLASS__ . '::' . strtoupper($name);
        if (!defined($const)) {
            throw new UnexpectedValueException(sprintf('Enum %s has no value defined for name %s', __CLASS__, $name));
        }
        return constant($const);
    }
}
// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ReportingAttributionModel::class, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\AttributionSettings_ReportingAttributionModel::class);
