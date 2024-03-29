<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/analytics/admin/v1alpha/audience.proto
namespace Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha;

use UnexpectedValueException;
/**
 * Specifies how to evaluate users for joining an Audience.
 *
 * Protobuf type <code>google.analytics.admin.v1alpha.AudienceFilterScope</code>
 */
class AudienceFilterScope
{
    /**
     * Scope is not specified.
     *
     * Generated from protobuf enum <code>AUDIENCE_FILTER_SCOPE_UNSPECIFIED = 0;</code>
     */
    const AUDIENCE_FILTER_SCOPE_UNSPECIFIED = 0;
    /**
     * User joins the Audience if the filter condition is met within one
     * event.
     *
     * Generated from protobuf enum <code>AUDIENCE_FILTER_SCOPE_WITHIN_SAME_EVENT = 1;</code>
     */
    const AUDIENCE_FILTER_SCOPE_WITHIN_SAME_EVENT = 1;
    /**
     * User joins the Audience if the filter condition is met within one
     * session.
     *
     * Generated from protobuf enum <code>AUDIENCE_FILTER_SCOPE_WITHIN_SAME_SESSION = 2;</code>
     */
    const AUDIENCE_FILTER_SCOPE_WITHIN_SAME_SESSION = 2;
    /**
     * User joins the Audience if the filter condition is met by any event
     * across any session.
     *
     * Generated from protobuf enum <code>AUDIENCE_FILTER_SCOPE_ACROSS_ALL_SESSIONS = 3;</code>
     */
    const AUDIENCE_FILTER_SCOPE_ACROSS_ALL_SESSIONS = 3;
    private static $valueToName = [self::AUDIENCE_FILTER_SCOPE_UNSPECIFIED => 'AUDIENCE_FILTER_SCOPE_UNSPECIFIED', self::AUDIENCE_FILTER_SCOPE_WITHIN_SAME_EVENT => 'AUDIENCE_FILTER_SCOPE_WITHIN_SAME_EVENT', self::AUDIENCE_FILTER_SCOPE_WITHIN_SAME_SESSION => 'AUDIENCE_FILTER_SCOPE_WITHIN_SAME_SESSION', self::AUDIENCE_FILTER_SCOPE_ACROSS_ALL_SESSIONS => 'AUDIENCE_FILTER_SCOPE_ACROSS_ALL_SESSIONS'];
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
