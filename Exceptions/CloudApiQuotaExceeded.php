<?php

namespace Piwik\Plugins\GoogleAnalyticsImporter\Exceptions;

class CloudApiQuotaExceeded extends \RuntimeException
{
    public function __construct($limit = 0)
    {
        parent::__construct('Daily cloud rate limit reached, try again tomorrow. (Note: GA by default sets a daily limit on the number'
            . ' of API requests made each day. This has been prorated to  ' . $limit . ' requests to your account. 
            This limit is prorated for each Matomo cloud customer. It looks like you\'ve reached your limit. Continue the import tomorrow.)');
    }
}