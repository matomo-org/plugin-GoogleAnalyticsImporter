<?php

namespace Piwik\Plugins\GoogleAnalyticsImporter\Exceptions;

use Piwik\Piwik;
class CloudApiQuotaExceeded extends \RuntimeException
{
    public function __construct($limit = 0)
    {
        parent::__construct(Piwik::translate('GoogleAnalyticsImporter_CloudRateLimitHelp', [$limit]));
    }
}
