<?php

namespace Matomo\Dependencies\GoogleAnalyticsImporter\GuzzleHttp\Promise;

/**
 * Exception that is set as the reason for a promise that has been cancelled.
 */
class CancellationException extends \Matomo\Dependencies\GoogleAnalyticsImporter\GuzzleHttp\Promise\RejectionException
{
}
