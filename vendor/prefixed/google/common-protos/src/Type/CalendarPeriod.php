<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/type/calendar_period.proto
namespace Google\Type;

use UnexpectedValueException;
/**
 * A `CalendarPeriod` represents the abstract concept of a time period that has
 * a canonical start. Grammatically, "the start of the current
 * `CalendarPeriod`." All calendar times begin at midnight UTC.
 *
 * Protobuf type <code>google.type.CalendarPeriod</code>
 */
class CalendarPeriod
{
    /**
     * Undefined period, raises an error.
     *
     * Generated from protobuf enum <code>CALENDAR_PERIOD_UNSPECIFIED = 0;</code>
     */
    const CALENDAR_PERIOD_UNSPECIFIED = 0;
    /**
     * A day.
     *
     * Generated from protobuf enum <code>DAY = 1;</code>
     */
    const DAY = 1;
    /**
     * A week. Weeks begin on Monday, following
     * [ISO 8601](https://en.wikipedia.org/wiki/ISO_week_date).
     *
     * Generated from protobuf enum <code>WEEK = 2;</code>
     */
    const WEEK = 2;
    /**
     * A fortnight. The first calendar fortnight of the year begins at the start
     * of week 1 according to
     * [ISO 8601](https://en.wikipedia.org/wiki/ISO_week_date).
     *
     * Generated from protobuf enum <code>FORTNIGHT = 3;</code>
     */
    const FORTNIGHT = 3;
    /**
     * A month.
     *
     * Generated from protobuf enum <code>MONTH = 4;</code>
     */
    const MONTH = 4;
    /**
     * A quarter. Quarters start on dates 1-Jan, 1-Apr, 1-Jul, and 1-Oct of each
     * year.
     *
     * Generated from protobuf enum <code>QUARTER = 5;</code>
     */
    const QUARTER = 5;
    /**
     * A half-year. Half-years start on dates 1-Jan and 1-Jul.
     *
     * Generated from protobuf enum <code>HALF = 6;</code>
     */
    const HALF = 6;
    /**
     * A year.
     *
     * Generated from protobuf enum <code>YEAR = 7;</code>
     */
    const YEAR = 7;
    private static $valueToName = [self::CALENDAR_PERIOD_UNSPECIFIED => 'CALENDAR_PERIOD_UNSPECIFIED', self::DAY => 'DAY', self::WEEK => 'WEEK', self::FORTNIGHT => 'FORTNIGHT', self::MONTH => 'MONTH', self::QUARTER => 'QUARTER', self::HALF => 'HALF', self::YEAR => 'YEAR'];
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
