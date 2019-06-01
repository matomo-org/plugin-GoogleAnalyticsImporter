<?php

require_once PIWIK_INCLUDE_PATH . '/plugins/GoogleAnalyticsImporter/vendor/autoload.php';

return [
    'GoogleAnalyticsImporter.googleClient' => new \Google_Client(),

    \Piwik\Plugins\GoogleAnalyticsImporter\Importer::class =>
        \DI\object()->constructorParameter('client', DI\get('GoogleAnalyticsImporter.googleClient')),

    'GoogleAnalyticsImporter.recordImporters' => [
        \Piwik\Plugins\GoogleAnalyticsImporter\Importers\Referrers\RecordImporter::class,
        \Piwik\Plugins\GoogleAnalyticsImporter\Importers\VisitsSummary\RecordImporter::class,
    ],
];
