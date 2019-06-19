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

    public function queryGoogleAnalyticsApi(Date $day)
    {
        $this->queryDimension($day, 'ga:countryIsoCode', Archiver::COUNTRY_RECORD_NAME);
        $this->queryDimension($day, 'ga:regionIsoCode', Archiver::REGION_RECORD_NAME);
        $this->queryCities($day);
    }

    private function queryDimension(Date $day, $dimension, $recordName)
    {
        $record = new DataTable();

        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, [$dimension], $this->getConversionAwareVisitMetrics());
        foreach ($table->getRows() as $row) {
            $label = $row->getMetadata($dimension);
            if (empty($label)) {
                $label = 'xx'; // TODO: is this the correct unknown value
            }

            $this->addRowToTable($record, $row, $label);
        }

        $this->insertRecord($recordName, $record);
        Common::destroy($record);
    }

    private function insertRecord($recordName, DataTable $record)
    {
        $blob = $record->getSerialized();
        $this->insertBlobRecord($recordName, $blob);
    }

    private function queryCities(Date $day)
    {
        $record = new DataTable();

        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, ['ga:city', 'ga:latitude', 'ga:longitude'], $this->getConversionAwareVisitMetrics());
        foreach ($table->getRows() as $row) {
            $label = $row->getMetadata('ga:city');
            if (empty($label)) {
                $label = 'xx'; // TODO: correct unknown value?
            }

            $lat = $row->getMetadata('ga:latitude');
            $long = $row->getMetadata('ga:longitude');

            $lat = round($lat, LocationProvider::GEOGRAPHIC_COORD_PRECISION);
            $long = round($long, LocationProvider::GEOGRAPHIC_COORD_PRECISION);

            $topLevelRow = $this->addRowToTable($record, $row, $label);

            // set latitude + longitude metadata
            $topLevelRow->setMetadata('lat', $lat);
            $topLevelRow->setMetadata('long', $long);
        }

        $this->insertRecord(Archiver::CITY_RECORD_NAME, $record);
        Common::destroy($record);
    }
}