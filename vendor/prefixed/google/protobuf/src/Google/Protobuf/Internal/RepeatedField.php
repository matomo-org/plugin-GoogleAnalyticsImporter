<?php

namespace Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal;

if (\false) {
    /**
     * This class is deprecated. Use Google\Protobuf\RepeatedField instead.
     * @deprecated
     */
    class RepeatedField extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\RepeatedField
    {
    }
}
class_exists(\Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\RepeatedField::class);
@trigger_error('Google\\Protobuf\\Internal\\RepeatedField is deprecated and will be removed in the next major release. Use Google\\Protobuf\\RepeatedField instead', \E_USER_DEPRECATED);
