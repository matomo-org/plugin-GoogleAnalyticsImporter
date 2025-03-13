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
use Piwik\Plugins\GeoIp2\LocationProvider\GeoIp2;
use Piwik\Plugins\UserCountry\Archiver;

class RecordImporterGA4 extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporterGA4
{
    public const PLUGIN_NAME = 'UserCountry';
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
        $regions = new DataTable();
        $gaQuery = $this->getGaClient();
        $table = $gaQuery->query($day, ['countryId', 'region', 'city'], $this->getConversionAwareVisitMetrics());
        $regionsList = \Piwik\Plugin\Manager::getInstance()->isPluginActivated('GeoIp2') ?  GeoIp2::getRegionNames() : [];
        foreach ($table->getRows() as $row) {
            $country = strtolower($row->getMetadata('countryId'));
            $region = $row->getMetadata('region');
            if ($country && $region && $regionsList) {
                $listToSearch = !empty($regionsList[strtoupper($country)]) ? $regionsList[strtoupper($country)] : [];
                if (!empty($listToSearch)) {
                    foreach ($listToSearch as $listKey => $list) {
                        // Need to use iconv as we store region as Île-de-France and GA4 returns as Ile-de-France
                        if (mb_strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $region)) === mb_strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $list))) {
                            $region = $listKey;
                            break;
                        }
                    }
                }
            }
            $city = $row->getMetadata('city');
            //            $lat = $row->getMetadata('ga:latitude'); // Not Available in GA4
            //            $long = $row->getMetadata('ga:longitude');  // Not Available in GA4

            $locationRegion = $region . Archiver::LOCATION_SEPARATOR . $country;
            $locationCity = $city . Archiver::LOCATION_SEPARATOR . $locationRegion;
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

                         */
            $this->addRowToTable($regions, $row, $locationRegion);
        }
        $this->insertRecord(Archiver::CITY_RECORD_NAME, $cities);
        Common::destroy($cities);
        $this->insertRecord(Archiver::REGION_RECORD_NAME, $regions);
        Common::destroy($regions);
    }
}
