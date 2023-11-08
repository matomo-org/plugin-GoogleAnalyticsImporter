<?php

namespace Matomo\Dependencies\GoogleAnalyticsImporter;

use Piwik\Option;
use Piwik\Url;
use Piwik\Container\Container;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\AuthorizationGA4;
require_once __DIR__ . '/../vendor/autoload.php';
return ['GoogleAnalyticsImporter.pingMysqlEverySecs' => null, 'GoogleAnalyticsImporter.useNohup' => \true, 'GoogleAnalyticsImporter.logToSingleFile' => \false, 'GoogleAnalyticsImporter.isClientConfigurable' => \true, 'log.processors' => \Piwik\DI::decorate(function ($previous, Container $container) {
    $idSite = (int) \getenv('MATOMO_GA_IMPORTER_LOG_TO_SINGLE_FILE');
    if (!empty($idSite)) {
        $previous[] = new \Piwik\Plugins\GoogleAnalyticsImporter\Logger\LogToSingleFileProcessor($idSite);
    }
    return $previous;
}), 'GoogleAnalyticsImporter.googleClientClass' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Client', 'GoogleAnalyticsImporter.googleClient' => function (Container $c) {
    $klass = $c->get('GoogleAnalyticsImporter.googleClientClass');
    /** @var \Google\Client $googleClient */
    $googleClient = new $klass();
    $googleClient->addScope(\Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\Analytics::ANALYTICS_READONLY);
    $googleClient->addScope(\Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\AnalyticsReporting::ANALYTICS_READONLY);
    $googleClient->setAccessType('offline');
    $googleClient->setApprovalPrompt('force');
    $redirectUrl = Url::getCurrentUrlWithoutQueryString() . '?module=GoogleAnalyticsImporter&action=processAuthCode';
    $googleClient->setRedirectUri($redirectUrl);
    return $googleClient;
}, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\Analytics::class => \Piwik\DI::autowire()->constructor(\Piwik\DI::get('GoogleAnalyticsImporter.googleClient')), \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\AnalyticsReporting::class => \Piwik\DI::autowire()->constructor(\Piwik\DI::get('GoogleAnalyticsImporter.googleClient')), 'GoogleAnalyticsImporter.googleAnalyticsDataClientClass' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Analytics\\Data\\V1beta\\BetaAnalyticsDataClient', 'GoogleAnalyticsImporter.googleAnalyticsAdminServiceClientClass' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Analytics\\Admin\\V1alpha\\AnalyticsAdminServiceClient', 'GoogleAnalyticsImporter.recordImporters' => [
    \Piwik\Plugins\GoogleAnalyticsImporter\Importers\VisitsSummary\RecordImporter::class,
    // must be first
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
], 'GoogleAnalyticsGA4Importer.clientConfiguration' => function (Container $c) {
    $config = @\json_decode(Option::get(AuthorizationGA4::CLIENT_CONFIG_OPTION_NAME), \true);
    $accessToken = @\json_decode(Option::get(AuthorizationGA4::ACCESS_TOKEN_OPTION_NAME), \true);
    return ['type' => 'authorized_user', 'client_id' => !empty($config['web']['client_id']) ? $config['web']['client_id'] : '', 'client_secret' => !empty($config['web']['client_secret']) ? $config['web']['client_secret'] : '', 'refresh_token' => !empty($accessToken['refresh_token']) ? $accessToken['refresh_token'] : ''];
}, 'GoogleAnalyticsGA4Importer.recordImporters' => [
    \Piwik\Plugins\GoogleAnalyticsImporter\Importers\VisitsSummary\RecordImporterGA4::class,
    // must be first
    \Piwik\Plugins\GoogleAnalyticsImporter\Importers\MarketingCampaignsReporting\RecordImporterGA4::class,
    \Piwik\Plugins\GoogleAnalyticsImporter\Importers\Referrers\RecordImporterGA4::class,
    \Piwik\Plugins\GoogleAnalyticsImporter\Importers\Actions\RecordImporterGA4::class,
    \Piwik\Plugins\GoogleAnalyticsImporter\Importers\DevicesDetection\RecordImporterGA4::class,
    \Piwik\Plugins\GoogleAnalyticsImporter\Importers\CustomVariables\RecordImporterGA4::class,
    \Piwik\Plugins\GoogleAnalyticsImporter\Importers\CustomDimensions\RecordImporterGA4::class,
    \Piwik\Plugins\GoogleAnalyticsImporter\Importers\Events\RecordImporterGA4::class,
    \Piwik\Plugins\GoogleAnalyticsImporter\Importers\Goals\RecordImporterGA4::class,
    \Piwik\Plugins\GoogleAnalyticsImporter\Importers\Resolution\RecordImporterGA4::class,
    \Piwik\Plugins\GoogleAnalyticsImporter\Importers\UserCountry\RecordImporterGA4::class,
    \Piwik\Plugins\GoogleAnalyticsImporter\Importers\UserLanguage\RecordImporterGA4::class,
    \Piwik\Plugins\GoogleAnalyticsImporter\Importers\VisitorInterest\RecordImporterGA4::class,
    \Piwik\Plugins\GoogleAnalyticsImporter\Importers\VisitTime\RecordImporterGA4::class,
    \Piwik\Plugins\GoogleAnalyticsImporter\Importers\VisitFrequency\RecordImporterGA4::class,
], 'diagnostics.optional' => \Piwik\DI::add([\Piwik\DI::get(\Piwik\Plugins\GoogleAnalyticsImporter\Diagnostic\RequiredFunctionsCheck::class), \Piwik\DI::get(\Piwik\Plugins\GoogleAnalyticsImporter\Diagnostic\RequiredExecutablesCheck::class)]), '\\Piwik\\Plugins\\GoogleAnalyticsImporter\\ApiQuotaHelper' => \Piwik\DI::create('\\Piwik\\Plugins\\GoogleAnalyticsImporter\\ApiQuotaHelper')];
