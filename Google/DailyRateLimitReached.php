<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\Google;

class DailyRateLimitReached extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Daily rate limit reached, try again tomorrow. (Note: GA by default sets a daily limit on the number' . ' of API requests made each day to 50000. It looks like you\'ve reached this limit. Continue the import tomorrow.)');
    }
}
