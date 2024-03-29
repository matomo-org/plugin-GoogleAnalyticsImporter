<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/analytics/admin/v1alpha/audience.proto
namespace Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\AudienceDimensionOrMetricFilter;

use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBType;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\RepeatedField;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBUtil;
/**
 * A filter for a string-type dimension that matches a particular pattern.
 *
 * Generated from protobuf message <code>google.analytics.admin.v1alpha.AudienceDimensionOrMetricFilter.StringFilter</code>
 */
class StringFilter extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\Message
{
    /**
     * Required. The match type for the string filter.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1alpha.AudienceDimensionOrMetricFilter.StringFilter.MatchType match_type = 1 [(.google.api.field_behavior) = REQUIRED];</code>
     */
    private $match_type = 0;
    /**
     * Required. The string value to be matched against.
     *
     * Generated from protobuf field <code>string value = 2 [(.google.api.field_behavior) = REQUIRED];</code>
     */
    private $value = '';
    /**
     * Optional. If true, the match is case-sensitive. If false, the match is
     * case-insensitive.
     *
     * Generated from protobuf field <code>bool case_sensitive = 3 [(.google.api.field_behavior) = OPTIONAL];</code>
     */
    private $case_sensitive = \false;
    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int $match_type
     *           Required. The match type for the string filter.
     *     @type string $value
     *           Required. The string value to be matched against.
     *     @type bool $case_sensitive
     *           Optional. If true, the match is case-sensitive. If false, the match is
     *           case-insensitive.
     * }
     */
    public function __construct($data = NULL)
    {
        \Matomo\Dependencies\GoogleAnalyticsImporter\GPBMetadata\Google\Analytics\Admin\V1Alpha\Audience::initOnce();
        parent::__construct($data);
    }
    /**
     * Required. The match type for the string filter.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1alpha.AudienceDimensionOrMetricFilter.StringFilter.MatchType match_type = 1 [(.google.api.field_behavior) = REQUIRED];</code>
     * @return int
     */
    public function getMatchType()
    {
        return $this->match_type;
    }
    /**
     * Required. The match type for the string filter.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1alpha.AudienceDimensionOrMetricFilter.StringFilter.MatchType match_type = 1 [(.google.api.field_behavior) = REQUIRED];</code>
     * @param int $var
     * @return $this
     */
    public function setMatchType($var)
    {
        GPBUtil::checkEnum($var, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\AudienceDimensionOrMetricFilter\StringFilter\MatchType::class);
        $this->match_type = $var;
        return $this;
    }
    /**
     * Required. The string value to be matched against.
     *
     * Generated from protobuf field <code>string value = 2 [(.google.api.field_behavior) = REQUIRED];</code>
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
    /**
     * Required. The string value to be matched against.
     *
     * Generated from protobuf field <code>string value = 2 [(.google.api.field_behavior) = REQUIRED];</code>
     * @param string $var
     * @return $this
     */
    public function setValue($var)
    {
        GPBUtil::checkString($var, True);
        $this->value = $var;
        return $this;
    }
    /**
     * Optional. If true, the match is case-sensitive. If false, the match is
     * case-insensitive.
     *
     * Generated from protobuf field <code>bool case_sensitive = 3 [(.google.api.field_behavior) = OPTIONAL];</code>
     * @return bool
     */
    public function getCaseSensitive()
    {
        return $this->case_sensitive;
    }
    /**
     * Optional. If true, the match is case-sensitive. If false, the match is
     * case-insensitive.
     *
     * Generated from protobuf field <code>bool case_sensitive = 3 [(.google.api.field_behavior) = OPTIONAL];</code>
     * @param bool $var
     * @return $this
     */
    public function setCaseSensitive($var)
    {
        GPBUtil::checkBool($var);
        $this->case_sensitive = $var;
        return $this;
    }
}
// Adding a class alias for backwards compatibility with the previous class name.
class_alias(StringFilter::class, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\AudienceDimensionOrMetricFilter_StringFilter::class);
