<?php

use Piwik\Url;

require_once PIWIK_INCLUDE_PATH . '/plugins/GoogleAnalyticsImporter/vendor/autoload.php';

return [
    'GoogleAnalyticsImporter.googleClient' => function () {
        $googleClient = new \Google_Client();
        $googleClient->addScope(\Google_Service_Analytics::ANALYTICS_READONLY);
        $googleClient->addScope(\Google_Service_AnalyticsReporting::ANALYTICS_READONLY);
        $googleClient->setAccessType('offline');
        $googleClient->setApprovalPrompt('force');
        $redirectUrl = Url::getCurrentUrlWithoutQueryString() . '?module=GoogleAnalyticsImporter&action=processAuthCode&idSite=';
        $googleClient->setRedirectUri($redirectUrl);
        return $googleClient;
    },

    \Piwik\Plugins\GoogleAnalyticsImporter\Importer::class =>
        \DI\object()->constructorParameter('client', DI\get('GoogleAnalyticsImporter.googleClient')),

    'GoogleAnalyticsImporter.recordImporters' => [
        \Piwik\Plugins\GoogleAnalyticsImporter\Importers\Referrers\RecordImporter::class,
        \Piwik\Plugins\GoogleAnalyticsImporter\Importers\VisitsSummary\RecordImporter::class,
        \Piwik\Plugins\GoogleAnalyticsImporter\Importers\Actions\RecordImporter::class,
        \Piwik\Plugins\GoogleAnalyticsImporter\Importers\DevicesDetection\RecordImporter::class,
        \Piwik\Plugins\GoogleAnalyticsImporter\Importers\CustomVariables\RecordImporter::class,
        \Piwik\Plugins\GoogleAnalyticsImporter\Importers\CustomDimensions\RecordImporter::class,
        \Piwik\Plugins\GoogleAnalyticsImporter\Importers\Events\RecordImporter::class,
        \Piwik\Plugins\GoogleAnalyticsImporter\Importers\Goals\RecordImporter::class,
        \Piwik\Plugins\GoogleAnalyticsImporter\Importers\Resolution\RecordImporter::class,
        \Piwik\Plugins\GoogleAnalyticsImporter\Importers\UserCountry\RecordImporter::class,
    ],
];
