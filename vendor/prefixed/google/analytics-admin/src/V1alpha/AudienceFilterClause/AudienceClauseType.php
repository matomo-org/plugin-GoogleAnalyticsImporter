<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/analytics/admin/v1alpha/audience.proto
namespace Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\AudienceFilterClause;

use UnexpectedValueException;
/**
 * Specifies whether this is an include or exclude filter clause.
 *
 * Protobuf type <code>google.analytics.admin.v1alpha.AudienceFilterClause.AudienceClauseType</code>
 */
class AudienceClauseType
{
    /**
     * Unspecified clause type.
     *
     * Generated from protobuf enum <code>AUDIENCE_CLAUSE_TYPE_UNSPECIFIED = 0;</code>
     */
    const AUDIENCE_CLAUSE_TYPE_UNSPECIFIED = 0;
    /**
     * Users will be included in the Audience if the filter clause is met.
     *
     * Generated from protobuf enum <code>INCLUDE = 1;</code>
     */
    const PBINCLUDE = 1;
    /**
     * Users will be excluded from the Audience if the filter clause is met.
     *
     * Generated from protobuf enum <code>EXCLUDE = 2;</code>
     */
    const EXCLUDE = 2;
    private static $valueToName = [self::AUDIENCE_CLAUSE_TYPE_UNSPECIFIED => 'AUDIENCE_CLAUSE_TYPE_UNSPECIFIED', self::PBINCLUDE => 'INCLUDE', self::EXCLUDE => 'EXCLUDE'];
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
            $pbconst = __CLASS__ . '::PB' . strtoupper($name);
            if (!defined($pbconst)) {
                throw new UnexpectedValueException(sprintf('Enum %s has no value defined for name %s', __CLASS__, $name));
            }
            return constant($pbconst);
        }
        return constant($const);
    }
}
// Adding a class alias for backwards compatibility with the previous class name.
class_alias(AudienceClauseType::class, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\AudienceFilterClause_AudienceClauseType::class);
