<?php

return [
    'GoogleAnalyticsImporter.googleClient' => new \Google_Client(),

    \Piwik\Plugins\GoogleAnalyticsImporter\ReportImporter::class => DI\object()
        ->constructor(DI\get('GoogleAnalyticsImporter.googleClient')),
];
