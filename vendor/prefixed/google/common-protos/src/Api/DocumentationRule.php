<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/api/documentation.proto
namespace Matomo\Dependencies\GoogleAnalyticsImporter\Google\Api;

use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBType;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\RepeatedField;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\GPBUtil;
/**
 * A documentation rule provides information about individual API elements.
 *
 * Generated from protobuf message <code>google.api.DocumentationRule</code>
 */
class DocumentationRule extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\Message
{
    /**
     * The selector is a comma-separated list of patterns. Each pattern is a
     * qualified name of the element which may end in "*", indicating a wildcard.
     * Wildcards are only allowed at the end and for a whole component of the
     * qualified name, i.e. "foo.*" is ok, but not "foo.b*" or "foo.*.bar". To
     * specify a default for all applicable elements, the whole pattern "*"
     * is used.
     *
     * Generated from protobuf field <code>string selector = 1;</code>
     */
    private $selector = '';
    /**
     * Description of the selected API(s).
     *
     * Generated from protobuf field <code>string description = 2;</code>
     */
    private $description = '';
    /**
     * Deprecation description of the selected element(s). It can be provided if an
     * element is marked as `deprecated`.
     *
     * Generated from protobuf field <code>string deprecation_description = 3;</code>
     */
    private $deprecation_description = '';
    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $selector
     *           The selector is a comma-separated list of patterns. Each pattern is a
     *           qualified name of the element which may end in "*", indicating a wildcard.
     *           Wildcards are only allowed at the end and for a whole component of the
     *           qualified name, i.e. "foo.*" is ok, but not "foo.b*" or "foo.*.bar". To
     *           specify a default for all applicable elements, the whole pattern "*"
     *           is used.
     *     @type string $description
     *           Description of the selected API(s).
     *     @type string $deprecation_description
     *           Deprecation description of the selected element(s). It can be provided if an
     *           element is marked as `deprecated`.
     * }
     */
    public function __construct($data = NULL)
    {
        \Matomo\Dependencies\GoogleAnalyticsImporter\GPBMetadata\Google\Api\Documentation::initOnce();
        parent::__construct($data);
    }
    /**
     * The selector is a comma-separated list of patterns. Each pattern is a
     * qualified name of the element which may end in "*", indicating a wildcard.
     * Wildcards are only allowed at the end and for a whole component of the
     * qualified name, i.e. "foo.*" is ok, but not "foo.b*" or "foo.*.bar". To
     * specify a default for all applicable elements, the whole pattern "*"
     * is used.
     *
     * Generated from protobuf field <code>string selector = 1;</code>
     * @return string
     */
    public function getSelector()
    {
        return $this->selector;
    }
    /**
     * The selector is a comma-separated list of patterns. Each pattern is a
     * qualified name of the element which may end in "*", indicating a wildcard.
     * Wildcards are only allowed at the end and for a whole component of the
     * qualified name, i.e. "foo.*" is ok, but not "foo.b*" or "foo.*.bar". To
     * specify a default for all applicable elements, the whole pattern "*"
     * is used.
     *
     * Generated from protobuf field <code>string selector = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setSelector($var)
    {
        GPBUtil::checkString($var, True);
        $this->selector = $var;
        return $this;
    }
    /**
     * Description of the selected API(s).
     *
     * Generated from protobuf field <code>string description = 2;</code>
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
    /**
     * Description of the selected API(s).
     *
     * Generated from protobuf field <code>string description = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setDescription($var)
    {
        GPBUtil::checkString($var, True);
        $this->description = $var;
        return $this;
    }
    /**
     * Deprecation description of the selected element(s). It can be provided if an
     * element is marked as `deprecated`.
     *
     * Generated from protobuf field <code>string deprecation_description = 3;</code>
     * @return string
     */
    public function getDeprecationDescription()
    {
        return $this->deprecation_description;
    }
    /**
     * Deprecation description of the selected element(s). It can be provided if an
     * element is marked as `deprecated`.
     *
     * Generated from protobuf field <code>string deprecation_description = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setDeprecationDescription($var)
    {
        GPBUtil::checkString($var, True);
        $this->deprecation_description = $var;
        return $this;
    }
}
