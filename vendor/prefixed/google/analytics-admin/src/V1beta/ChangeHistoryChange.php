<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/analytics/admin/v1beta/resources.proto
namespace Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1beta;

use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBType;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\RepeatedField;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBUtil;
/**
 * A description of a change to a single Google Analytics resource.
 *
 * Generated from protobuf message <code>google.analytics.admin.v1beta.ChangeHistoryChange</code>
 */
class ChangeHistoryChange extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\Message
{
    /**
     * Resource name of the resource whose changes are described by this entry.
     *
     * Generated from protobuf field <code>string resource = 1;</code>
     */
    private $resource = '';
    /**
     * The type of action that changed this resource.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1beta.ActionType action = 2;</code>
     */
    private $action = 0;
    /**
     * Resource contents from before the change was made. If this resource was
     * created in this change, this field will be missing.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1beta.ChangeHistoryChange.ChangeHistoryResource resource_before_change = 3;</code>
     */
    private $resource_before_change = null;
    /**
     * Resource contents from after the change was made. If this resource was
     * deleted in this change, this field will be missing.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1beta.ChangeHistoryChange.ChangeHistoryResource resource_after_change = 4;</code>
     */
    private $resource_after_change = null;
    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $resource
     *           Resource name of the resource whose changes are described by this entry.
     *     @type int $action
     *           The type of action that changed this resource.
     *     @type \Google\Analytics\Admin\V1beta\ChangeHistoryChange\ChangeHistoryResource $resource_before_change
     *           Resource contents from before the change was made. If this resource was
     *           created in this change, this field will be missing.
     *     @type \Google\Analytics\Admin\V1beta\ChangeHistoryChange\ChangeHistoryResource $resource_after_change
     *           Resource contents from after the change was made. If this resource was
     *           deleted in this change, this field will be missing.
     * }
     */
    public function __construct($data = NULL)
    {
        \Matomo\Dependencies\GoogleAnalyticsImporter\GPBMetadata\Google\Analytics\Admin\V1Beta\Resources::initOnce();
        parent::__construct($data);
    }
    /**
     * Resource name of the resource whose changes are described by this entry.
     *
     * Generated from protobuf field <code>string resource = 1;</code>
     * @return string
     */
    public function getResource()
    {
        return $this->resource;
    }
    /**
     * Resource name of the resource whose changes are described by this entry.
     *
     * Generated from protobuf field <code>string resource = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setResource($var)
    {
        GPBUtil::checkString($var, True);
        $this->resource = $var;
        return $this;
    }
    /**
     * The type of action that changed this resource.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1beta.ActionType action = 2;</code>
     * @return int
     */
    public function getAction()
    {
        return $this->action;
    }
    /**
     * The type of action that changed this resource.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1beta.ActionType action = 2;</code>
     * @param int $var
     * @return $this
     */
    public function setAction($var)
    {
        GPBUtil::checkEnum($var, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1beta\ActionType::class);
        $this->action = $var;
        return $this;
    }
    /**
     * Resource contents from before the change was made. If this resource was
     * created in this change, this field will be missing.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1beta.ChangeHistoryChange.ChangeHistoryResource resource_before_change = 3;</code>
     * @return \Google\Analytics\Admin\V1beta\ChangeHistoryChange\ChangeHistoryResource|null
     */
    public function getResourceBeforeChange()
    {
        return $this->resource_before_change;
    }
    public function hasResourceBeforeChange()
    {
        return isset($this->resource_before_change);
    }
    public function clearResourceBeforeChange()
    {
        unset($this->resource_before_change);
    }
    /**
     * Resource contents from before the change was made. If this resource was
     * created in this change, this field will be missing.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1beta.ChangeHistoryChange.ChangeHistoryResource resource_before_change = 3;</code>
     * @param \Google\Analytics\Admin\V1beta\ChangeHistoryChange\ChangeHistoryResource $var
     * @return $this
     */
    public function setResourceBeforeChange($var)
    {
        GPBUtil::checkMessage($var, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1beta\ChangeHistoryChange\ChangeHistoryResource::class);
        $this->resource_before_change = $var;
        return $this;
    }
    /**
     * Resource contents from after the change was made. If this resource was
     * deleted in this change, this field will be missing.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1beta.ChangeHistoryChange.ChangeHistoryResource resource_after_change = 4;</code>
     * @return \Google\Analytics\Admin\V1beta\ChangeHistoryChange\ChangeHistoryResource|null
     */
    public function getResourceAfterChange()
    {
        return $this->resource_after_change;
    }
    public function hasResourceAfterChange()
    {
        return isset($this->resource_after_change);
    }
    public function clearResourceAfterChange()
    {
        unset($this->resource_after_change);
    }
    /**
     * Resource contents from after the change was made. If this resource was
     * deleted in this change, this field will be missing.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1beta.ChangeHistoryChange.ChangeHistoryResource resource_after_change = 4;</code>
     * @param \Google\Analytics\Admin\V1beta\ChangeHistoryChange\ChangeHistoryResource $var
     * @return $this
     */
    public function setResourceAfterChange($var)
    {
        GPBUtil::checkMessage($var, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1beta\ChangeHistoryChange\ChangeHistoryResource::class);
        $this->resource_after_change = $var;
        return $this;
    }
}