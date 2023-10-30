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
class RecordImporterGA4 extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporterGA4
{
    const PLUGIN_NAME = 'UserCountry';
    public function importRecords(Date $day)
    {
        $this->queryDimension($day, 'countryId', Archiver::COUNTRY_RECORD_NAME);
        $this->queryRegionsAndCities($day);
    }
    private function queryDimension(Date $day, $dimension, $recordName)
    {
        $record = new DataTable();
        $gaQuery = $this->getGaClient();
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
        //        $regions = new DataTable(); // Not Available in GA4
        $gaQuery = $this->getGaClient();
        $table = $gaQuery->query($day, ['countryId', 'city'], $this->getConversionAwareVisitMetrics());
        foreach ($table->getRows() as $row) {
            $country = strtolower($row->getMetadata('countryId'));
            //            $region = $row->getMetadata('ga:regionIsoCode'); // Not Available in GA4
            $city = $row->getMetadata('city');
            //            $lat = $row->getMetadata('ga:latitude'); // Not Available in GA4
            //            $long = $row->getMetadata('ga:longitude');  // Not Available in GA4
            /** Not available in GA4
                        // GA returns region as COUNTRY-REGION, we only want the last part here
                        $regionParts = explode('-', $region);
                        $region = end($regionParts);
            
                        $locationRegion = $region . Archiver::LOCATION_SEPARATOR . $country;
                        $locationCity = $city . Archiver::LOCATION_SEPARATOR . $locationRegion;
                         */
            if (!empty($city)) {
                $topLevelRowCity = $this->addRowToTable($cities, $row, $city);
            }
            /** Not available in GA4
                        if (is_numeric($lat)
                            && is_numeric($long)
                        ) {
                            $lat = round($lat, LocationProvider::GEOGRAPHIC_COORD_PRECISION);
                            $long = round($long, LocationProvider::GEOGRAPHIC_COORD_PRECISION);
            
                            // set latitude + longitude metadata
                            $topLevelRowCity->setMetadata('lat', $lat);
                            $topLevelRowCity->setMetadata('long', $long);
                        }
            
                        $this->addRowToTable($regions, $row, $locationRegion);
                         */
        }
        $this->insertRecord(Archiver::CITY_RECORD_NAME, $cities);
        Common::destroy($cities);
        /** Not available in GA4
            $this->insertRecord(Archiver::REGION_RECORD_NAME, $regions);
            Common::destroy($regions);
             */
    }
}
