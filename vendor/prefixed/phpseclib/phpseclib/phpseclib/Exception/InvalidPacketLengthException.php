<?php

namespace Matomo\Dependencies\GoogleAnalyticsImporter\phpseclib3\Exception;

/**
 * Indicates an absent or malformed packet length header
 */
class InvalidPacketLengthException extends ConnectionClosedException
{
}
