<?php

use Piwik\Url;

require_once __DIR__ . '/../vendor/autoload.php';

return [
    'GoogleAnalyticsImporter.pingMysqlEverySecs' => null,

    'GoogleAnalyticsImporter.googleClientClass' => 'Google_Client',
    'GoogleAnalyticsImporter.googleClient' => function (\Psr\Container\ContainerInterface $c) {
        $klass = $c->get('GoogleAnalyticsImporter.googleClientClass');

        /** @var \Google_Client $googleClient */
        $googleClient = new $klass();
        $googleClient->addScope(\Google_Service_Analytics::ANALYTICS_READONLY);
        $googleClient->addScope(\Google_Service_AnalyticsReporting::ANALYTICS_READONLY);
        $googleClient->setAccessType('offline');
        $googleClient->setApprovalPrompt('force');
        $redirectUrl = Url::getCurrentUrlWithoutQueryString() . '?module=GoogleAnalyticsImporter&action=processAuthCode';
        $googleClient->setRedirectUri($redirectUrl);
        return $googleClient;
    },

    Google_Service_Analytics::class => \DI\object()->constructor(\DI\get('GoogleAnalyticsImporter.googleClient')),
    Google_Service_AnalyticsReporting::class => \DI\object()->constructor(\DI\get('GoogleAnalyticsImporter.googleClient')),

    'GoogleAnalyticsImporter.recordImporters' => [
        \Piwik\Plugins\GoogleAnalyticsImporter\Importers\VisitsSummary\RecordImporter::class, // must be first

        \Piwik\Plugins\GoogleAnalyticsImporter\Importers\MarketingCampaignsReporting\RecordImporter::class,
        \Piwik\Plugins\GoogleAnalyticsImporter\Importers\Referrers\RecordImporter::class,
        \Piwik\Plugins\GoogleAnalyticsImporter\Importers\Actions\RecordImporter::class,
        \Piwik\Plugins\GoogleAnalyticsImporter\Importers\DevicesDetection\RecordImporter::class,
        \Piwik\Plugins\GoogleAnalyticsImporter\Importers\CustomVariables\RecordImporter::class,
        \Piwik\Plugins\GoogleAnalyticsImporter\Importers\CustomDimensions\RecordImporter::class,
        \Piwik\Plugins\GoogleAnalyticsImporter\Importers\Events\RecordImporter::class,
        \Piwik\Plugins\GoogleAnalyticsImporter\Importers\Goals\RecordImporter::class,
        \Piwik\Plugins\GoogleAnalyticsImporter\Importers\Resolution\RecordImporter::class,
        \Piwik\Plugins\GoogleAnalyticsImporter\Importers\UserCountry\RecordImporter::class,
        \Piwik\Plugins\GoogleAnalyticsImporter\Importers\UserLanguage\RecordImporter::class,
        \Piwik\Plugins\GoogleAnalyticsImporter\Importers\VisitorInterest\RecordImporter::class,
        \Piwik\Plugins\GoogleAnalyticsImporter\Importers\VisitTime\RecordImporter::class,
        \Piwik\Plugins\GoogleAnalyticsImporter\Importers\VisitFrequency\RecordImporter::class,
    ],

    'diagnostics.optional' => \DI\add([
        \DI\get(\Piwik\Plugins\GoogleAnalyticsImporter\Diagnostic\RequiredFunctionsCheck::class),
        \DI\get(\Piwik\Plugins\GoogleAnalyticsImporter\Diagnostic\RequiredExecutablesCheck::class),
    ]),
];
