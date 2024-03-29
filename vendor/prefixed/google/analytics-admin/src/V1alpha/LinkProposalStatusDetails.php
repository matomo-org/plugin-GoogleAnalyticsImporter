<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/analytics/admin/v1alpha/resources.proto
namespace Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha;

use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBType;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\RepeatedField;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBUtil;
/**
 * Status information for a link proposal.
 *
 * Generated from protobuf message <code>google.analytics.admin.v1alpha.LinkProposalStatusDetails</code>
 */
class LinkProposalStatusDetails extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\Message
{
    /**
     * Output only. The source of this proposal.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1alpha.LinkProposalInitiatingProduct link_proposal_initiating_product = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    private $link_proposal_initiating_product = 0;
    /**
     * Output only. The email address of the user that proposed this linkage.
     *
     * Generated from protobuf field <code>string requestor_email = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    private $requestor_email = '';
    /**
     * Output only. The state of this proposal.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1alpha.LinkProposalState link_proposal_state = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     */
    private $link_proposal_state = 0;
    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int $link_proposal_initiating_product
     *           Output only. The source of this proposal.
     *     @type string $requestor_email
     *           Output only. The email address of the user that proposed this linkage.
     *     @type int $link_proposal_state
     *           Output only. The state of this proposal.
     * }
     */
    public function __construct($data = NULL)
    {
        \Matomo\Dependencies\GoogleAnalyticsImporter\GPBMetadata\Google\Analytics\Admin\V1Alpha\Resources::initOnce();
        parent::__construct($data);
    }
    /**
     * Output only. The source of this proposal.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1alpha.LinkProposalInitiatingProduct link_proposal_initiating_product = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return int
     */
    public function getLinkProposalInitiatingProduct()
    {
        return $this->link_proposal_initiating_product;
    }
    /**
     * Output only. The source of this proposal.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1alpha.LinkProposalInitiatingProduct link_proposal_initiating_product = 1 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param int $var
     * @return $this
     */
    public function setLinkProposalInitiatingProduct($var)
    {
        GPBUtil::checkEnum($var, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\LinkProposalInitiatingProduct::class);
        $this->link_proposal_initiating_product = $var;
        return $this;
    }
    /**
     * Output only. The email address of the user that proposed this linkage.
     *
     * Generated from protobuf field <code>string requestor_email = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return string
     */
    public function getRequestorEmail()
    {
        return $this->requestor_email;
    }
    /**
     * Output only. The email address of the user that proposed this linkage.
     *
     * Generated from protobuf field <code>string requestor_email = 2 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param string $var
     * @return $this
     */
    public function setRequestorEmail($var)
    {
        GPBUtil::checkString($var, True);
        $this->requestor_email = $var;
        return $this;
    }
    /**
     * Output only. The state of this proposal.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1alpha.LinkProposalState link_proposal_state = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @return int
     */
    public function getLinkProposalState()
    {
        return $this->link_proposal_state;
    }
    /**
     * Output only. The state of this proposal.
     *
     * Generated from protobuf field <code>.google.analytics.admin.v1alpha.LinkProposalState link_proposal_state = 3 [(.google.api.field_behavior) = OUTPUT_ONLY];</code>
     * @param int $var
     * @return $this
     */
    public function setLinkProposalState($var)
    {
        GPBUtil::checkEnum($var, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\LinkProposalState::class);
        $this->link_proposal_state = $var;
        return $this;
    }
}
