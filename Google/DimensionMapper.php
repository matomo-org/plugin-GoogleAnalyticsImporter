<?php


namespace Piwik\Plugins\GoogleAnalyticsImporter\Google;


use Piwik\Piwik;
use Piwik\Plugins\Actions\Columns\EntryPageTitle;
use Piwik\Plugins\Actions\Columns\EntryPageUrl;
use Piwik\Plugins\Actions\Columns\ExitPageUrl;
use Piwik\Plugins\Actions\Columns\PageUrl;
use Piwik\Plugins\Actions\Columns\VisitTotalInteractions;
use Piwik\Plugins\CoreHome\Columns\VisitorReturning;
use Piwik\Plugins\CoreHome\Columns\VisitsCount;
use Piwik\Plugins\DevicesDetection\Columns\BrowserName;
use Piwik\Plugins\DevicesDetection\Columns\BrowserVersion;
use Piwik\Plugins\GeoIp2\Columns\Region;
use Piwik\Plugins\HeatmapSessionRecording\Columns\Metrics\OperatingSystem;
use Piwik\Plugins\Resolution\Columns\Resolution;
use Piwik\Plugins\UserCountry\Columns\City;
use Piwik\Plugins\UserCountry\Columns\Continent;
use Piwik\Plugins\UserCountry\Columns\Country;
use Piwik\Plugins\UserCountry\Columns\Latitude;
use Piwik\Plugins\UserCountry\Columns\Longitude;
use Piwik\Plugins\UserLanguage\Columns\Language;
use Piwik\Plugins\VisitorInterest\Columns\VisitsByDaysSinceLastVisit;

// TODO: allow ga:userDefinedValue to be mapped to custom dimension
class DimensionMapper
{
    public function getActionIdentifierDimensions()
    {
        return [
            'ga:browser',
            'ga:mobileDeviceInfo',

            'ga:countryIsoCode',
            'ga:city',

            'ga:date',
            'ga:nthMinute',
        ];
    }
/*
- ga:browser
- ga:browserVersion
- ga:operatingSystem
- ga:operatingSystemVersion
- ga:mobileDeviceInfo
- ga:browserSize
- ga:continent
- ga:countryId
- ga:regionId
- ga:cityId
- ga:latitude
- ga:longitude
- ga:screenResolution
- ga:language
 */
    public function getVisitDimensionMappings()
    {
        // TODO: add rest of mappings?
        $mappings = [
            BrowserName::class => 'ga:browser',
            BrowserVersion::class => 'ga:browserVersion',
            OperatingSystem::class => 'ga:operatingSystem',
            Continent::class => 'ga:continent',
            Country::class => 'ga:countryId',
            Region::class => 'ga:regionId',
            City::class => 'ga:city',
            Latitude::class => 'ga:latitude',
            Longitude::class => 'ga:longitude',
            Resolution::class => 'ga:screenResolution',
            Language::class => 'ga:language',





            /*
            VisitorReturning::class => 'ga:userType',
            VisitsCount::class => 'ga:sessionCount',
            VisitsByDaysSinceLastVisit::class => 'ga:daysSinceLastSession',
            PageUrl::class => 'ga:pagePath',

            EntryPageUrl::class => 'ga:landingPagePath',
            ExitPageUrl::class => 'ga:exitPagePath',
            */
            // TODO: Entry/ExitPageTitle?
            // VisitTotalActions => computed manually
        ];

        /**
         * TODO
         */
        Piwik::postEvent('GoogleAnalyticsImporter.mapVisitDimensions', [&$mappings]);

        return $mappings;
    }

    public function getActionDimensionMappings()
    {
        // TODO
    }
}