<?php

namespace Matomo\Dependencies\GoogleAnalyticsImporter\GuzzleHttp;

use Matomo\Dependencies\GoogleAnalyticsImporter\Psr\Http\Message\MessageInterface;
interface BodySummarizerInterface
{
    /**
     * Returns a summarized message body.
     */
    public function summarize(MessageInterface $message) : ?string;
}
