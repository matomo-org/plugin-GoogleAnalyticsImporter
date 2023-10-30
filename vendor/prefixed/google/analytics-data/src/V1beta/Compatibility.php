<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/analytics/data/v1beta/data.proto
namespace Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta;

use UnexpectedValueException;
/**
 * The compatibility types for a single dimension or metric.
 *
 * Protobuf type <code>google.analytics.data.v1beta.Compatibility</code>
 */
class Compatibility
{
    /**
     * Unspecified compatibility.
     *
     * Generated from protobuf enum <code>COMPATIBILITY_UNSPECIFIED = 0;</code>
     */
    const COMPATIBILITY_UNSPECIFIED = 0;
    /**
     * The dimension or metric is compatible. This dimension or metric can be
     * successfully added to a report.
     *
     * Generated from protobuf enum <code>COMPATIBLE = 1;</code>
     */
    const COMPATIBLE = 1;
    /**
     * The dimension or metric is incompatible. This dimension or metric cannot be
     * successfully added to a report.
     *
     * Generated from protobuf enum <code>INCOMPATIBLE = 2;</code>
     */
    const INCOMPATIBLE = 2;
    private static $valueToName = [self::COMPATIBILITY_UNSPECIFIED => 'COMPATIBILITY_UNSPECIFIED', self::COMPATIBLE => 'COMPATIBLE', self::INCOMPATIBLE => 'INCOMPATIBLE'];
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