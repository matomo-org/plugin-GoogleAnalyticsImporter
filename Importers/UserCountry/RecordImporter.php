<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\Importers\UserCountry;

use Piwik\Common;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Plugins\UserCountry\Archiver;
use Piwik\Plugins\UserCountry\LocationProvider;
class RecordImporter extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporter
{
    const PLUGIN_NAME = 'UserCountry';
    public function importRecords(Date $day)
    {
        $this->queryDimension($day, 'ga:countryIsoCode', Archiver::COUNTRY_RECORD_NAME);
        $this->queryRegionsAndCities($day);
    }
    private function queryDimension(Date $day, $dimension, $recordName)
    {
        $record = new DataTable();
        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, [$dimension], $this->getConversionAwareVisitMetrics());
        foreach ($table->getRows() as $row) {
            $label = strtolower($row->getMetadata($dimension));
            if (empty($label)) {
                $label = 'xx';
            }
            $this->addRowToTable($record, $row, $label);
        }
        $this->insertRecord($recordName, $record);
        Common::destroy($record);
    }
    private function queryRegionsAndCities(Date $day)
    {
        $cities = new DataTable();
        $regions = new DataTable();
        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, ['ga:countryIsoCode', 'ga:regionIsoCode', 'ga:city', 'ga:latitude', 'ga:longitude'], $this->getConversionAwareVisitMetrics());
        foreach ($table->getRows() as $row) {
            $country = strtolower($row->getMetadata('ga:countryIsoCode'));
            $region = $row->getMetadata('ga:regionIsoCode');
            $city = $row->getMetadata('ga:city');
            $lat = $row->getMetadata('ga:latitude');
            $long = $row->getMetadata('ga:longitude');
            // GA returns region as COUNTRY-REGION, we only want the last part here
            $regionParts = explode('-', $region);
            $region = end($regionParts);
            $locationRegion = $region . Archiver::LOCATION_SEPARATOR . $country;
            $locationCity = $city . Archiver::LOCATION_SEPARATOR . $locationRegion;
            $topLevelRowCity = $this->addRowToTable($cities, $row, $locationCity);
            if (is_numeric($lat) && is_numeric($long)) {
                $lat = round($lat, LocationProvider::GEOGRAPHIC_COORD_PRECISION);
                $long = round($long, LocationProvider::GEOGRAPHIC_COORD_PRECISION);
                // set latitude + longitude metadata
                $topLevelRowCity->setMetadata('lat', $lat);
                $topLevelRowCity->setMetadata('long', $long);
            }
            $this->addRowToTable($regions, $row, $locationRegion);
        }
        $this->insertRecord(Archiver::CITY_RECORD_NAME, $cities);
        Common::destroy($cities);
        $this->insertRecord(Archiver::REGION_RECORD_NAME, $regions);
        Common::destroy($regions);
    }
}
