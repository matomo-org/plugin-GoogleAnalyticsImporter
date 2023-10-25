<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/analytics/admin/v1alpha/analytics_admin.proto

namespace Google\Analytics\Admin\V1alpha;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Response message for ListMeasurementProtocolSecret RPC
 *
 * Generated from protobuf message <code>google.analytics.admin.v1alpha.ListMeasurementProtocolSecretsResponse</code>
 */
class ListMeasurementProtocolSecretsResponse extends \Google\Protobuf\Internal\Message
{
    /**
     * A list of secrets for the parent stream specified in the request.
     *
     * Generated from protobuf field <code>repeated .google.analytics.admin.v1alpha.MeasurementProtocolSecret measurement_protocol_secrets = 1;</code>
     */
    private $measurement_protocol_secrets;
    /**
     * A token, which can be sent as `page_token` to retrieve the next page.
     * If this field is omitted, there are no subsequent pages.
     *
     * Generated from protobuf field <code>string next_page_token = 2;</code>
     */
    private $next_page_token = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type array<\Google\Analytics\Admin\V1alpha\MeasurementProtocolSecret>|\Google\Protobuf\Internal\RepeatedField $measurement_protocol_secrets
     *           A list of secrets for the parent stream specified in the request.
     *     @type string $next_page_token
     *           A token, which can be sent as `page_token` to retrieve the next page.
     *           If this field is omitted, there are no subsequent pages.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Analytics\Admin\V1Alpha\AnalyticsAdmin::initOnce();
        parent::__construct($data);
    }

    /**
     * A list of secrets for the parent stream specified in the request.
     *
     * Generated from protobuf field <code>repeated .google.analytics.admin.v1alpha.MeasurementProtocolSecret measurement_protocol_secrets = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getMeasurementProtocolSecrets()
    {
        return $this->measurement_protocol_secrets;
    }

    /**
     * A list of secrets for the parent stream specified in the request.
     *
     * Generated from protobuf field <code>repeated .google.analytics.admin.v1alpha.MeasurementProtocolSecret measurement_protocol_secrets = 1;</code>
     * @param array<\Google\Analytics\Admin\V1alpha\MeasurementProtocolSecret>|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setMeasurementProtocolSecrets($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Analytics\Admin\V1alpha\MeasurementProtocolSecret::class);
        $this->measurement_protocol_secrets = $arr;

        return $this;
    }

    /**
     * A token, which can be sent as `page_token` to retrieve the next page.
     * If this field is omitted, there are no subsequent pages.
     *
     * Generated from protobuf field <code>string next_page_token = 2;</code>
     * @return string
     */
    public function getNextPageToken()
    {
        return $this->next_page_token;
    }

    /**
     * A token, which can be sent as `page_token` to retrieve the next page.
     * If this field is omitted, there are no subsequent pages.
     *
     * Generated from protobuf field <code>string next_page_token = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setNextPageToken($var)
    {
        GPBUtil::checkString($var, True);
        $this->next_page_token = $var;

        return $this;
    }

}

