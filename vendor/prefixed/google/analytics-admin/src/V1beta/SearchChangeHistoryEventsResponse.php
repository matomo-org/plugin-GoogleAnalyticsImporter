<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/analytics/admin/v1beta/analytics_admin.proto
namespace Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1beta;

use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBType;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\RepeatedField;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBUtil;
/**
 * Response message for SearchAccounts RPC.
 *
 * Generated from protobuf message <code>google.analytics.admin.v1beta.SearchChangeHistoryEventsResponse</code>
 */
class SearchChangeHistoryEventsResponse extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\Message
{
    /**
     * Results that were accessible to the caller.
     *
     * Generated from protobuf field <code>repeated .google.analytics.admin.v1beta.ChangeHistoryEvent change_history_events = 1;</code>
     */
    private $change_history_events;
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
     *     @type array<\Google\Analytics\Admin\V1beta\ChangeHistoryEvent>|\Google\Protobuf\Internal\RepeatedField $change_history_events
     *           Results that were accessible to the caller.
     *     @type string $next_page_token
     *           A token, which can be sent as `page_token` to retrieve the next page.
     *           If this field is omitted, there are no subsequent pages.
     * }
     */
    public function __construct($data = NULL)
    {
        \Matomo\Dependencies\GoogleAnalyticsImporter\GPBMetadata\Google\Analytics\Admin\V1Beta\AnalyticsAdmin::initOnce();
        parent::__construct($data);
    }
    /**
     * Results that were accessible to the caller.
     *
     * Generated from protobuf field <code>repeated .google.analytics.admin.v1beta.ChangeHistoryEvent change_history_events = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getChangeHistoryEvents()
    {
        return $this->change_history_events;
    }
    /**
     * Results that were accessible to the caller.
     *
     * Generated from protobuf field <code>repeated .google.analytics.admin.v1beta.ChangeHistoryEvent change_history_events = 1;</code>
     * @param array<\Google\Analytics\Admin\V1beta\ChangeHistoryEvent>|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setChangeHistoryEvents($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBType::MESSAGE, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1beta\ChangeHistoryEvent::class);
        $this->change_history_events = $arr;
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
