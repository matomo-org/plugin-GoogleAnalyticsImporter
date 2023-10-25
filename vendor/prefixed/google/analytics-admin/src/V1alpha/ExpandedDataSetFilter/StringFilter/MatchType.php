<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/analytics/admin/v1alpha/expanded_data_set.proto
namespace Google\Analytics\Admin\V1alpha\ExpandedDataSetFilter\StringFilter;

use UnexpectedValueException;
/**
 * The match type for the string filter.
 *
 * Protobuf type <code>google.analytics.admin.v1alpha.ExpandedDataSetFilter.StringFilter.MatchType</code>
 */
class MatchType
{
    /**
     * Unspecified
     *
     * Generated from protobuf enum <code>MATCH_TYPE_UNSPECIFIED = 0;</code>
     */
    const MATCH_TYPE_UNSPECIFIED = 0;
    /**
     * Exact match of the string value.
     *
     * Generated from protobuf enum <code>EXACT = 1;</code>
     */
    const EXACT = 1;
    /**
     * Contains the string value.
     *
     * Generated from protobuf enum <code>CONTAINS = 2;</code>
     */
    const CONTAINS = 2;
    private static $valueToName = [self::MATCH_TYPE_UNSPECIFIED => 'MATCH_TYPE_UNSPECIFIED', self::EXACT => 'EXACT', self::CONTAINS => 'CONTAINS'];
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
class_alias(\Google\Analytics\Admin\V1alpha\ExpandedDataSetFilter\StringFilter\MatchType::class, \Google\Analytics\Admin\V1alpha\ExpandedDataSetFilter_StringFilter_MatchType::class);
