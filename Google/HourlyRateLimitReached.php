<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\Google;

class HourlyRateLimitReached extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Hourly rate limit reached, try again after an hour. (Note: GA by default sets a hourly limit on the number' . ' of API requests made per hour to 5000. It looks like you\'ve reached this limit. Continue the import after an hour.)');
    }
}
