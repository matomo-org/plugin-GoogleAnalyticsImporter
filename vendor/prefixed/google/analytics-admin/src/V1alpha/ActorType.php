<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/analytics/admin/v1alpha/resources.proto
namespace Google\Analytics\Admin\V1alpha;

use UnexpectedValueException;
/**
 * Different kinds of actors that can make changes to Google Analytics
 * resources.
 *
 * Protobuf type <code>google.analytics.admin.v1alpha.ActorType</code>
 */
class ActorType
{
    /**
     * Unknown or unspecified actor type.
     *
     * Generated from protobuf enum <code>ACTOR_TYPE_UNSPECIFIED = 0;</code>
     */
    const ACTOR_TYPE_UNSPECIFIED = 0;
    /**
     * Changes made by the user specified in actor_email.
     *
     * Generated from protobuf enum <code>USER = 1;</code>
     */
    const USER = 1;
    /**
     * Changes made by the Google Analytics system.
     *
     * Generated from protobuf enum <code>SYSTEM = 2;</code>
     */
    const SYSTEM = 2;
    /**
     * Changes made by Google Analytics support team staff.
     *
     * Generated from protobuf enum <code>SUPPORT = 3;</code>
     */
    const SUPPORT = 3;
    private static $valueToName = [self::ACTOR_TYPE_UNSPECIFIED => 'ACTOR_TYPE_UNSPECIFIED', self::USER => 'USER', self::SYSTEM => 'SYSTEM', self::SUPPORT => 'SUPPORT'];
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
