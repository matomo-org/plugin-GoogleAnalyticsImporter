<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\Importers\UserLanguage;

use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Intl\Data\Provider\RegionDataProvider;
use Piwik\Plugins\UserLanguage\Archiver;
class RecordImporter extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporter
{
    const PLUGIN_NAME = 'UserLanguage';
    public function importRecords(Date $day)
    {
        $dimension = 'ga:language';
        $recordName = Archiver::LANGUAGE_RECORD_NAME;
        /** @var RegionDataProvider $regionDataProvider */
        $regionDataProvider = StaticContainer::get('Piwik\\Intl\\Data\\Provider\\RegionDataProvider');
        $countryCodes = $regionDataProvider->getCountryList($includeInternalCodes = \true);
        $record = new DataTable();
        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, [$dimension], $this->getConversionAwareVisitMetrics());
        foreach ($table->getRows() as $row) {
            $label = $row->getMetadata($dimension);
            if (empty($label)) {
                $label = self::NOT_SET_IN_GA_LABEL;
            }
            $langCode = Common::extractLanguageCodeFromBrowserLanguage($label);
            $countryCode = Common::extractCountryCodeFromBrowserLanguage($label, $countryCodes, $enableLanguageToCountryGuess = \true);
            if ($countryCode == 'xx' || $countryCode == $langCode) {
                $this->addRowToTable($record, $row, $langCode);
            } else {
                $this->addRowToTable($record, $row, $langCode . '-' . $countryCode);
            }
        }
        $this->insertRecord($recordName, $record);
        Common::destroy($record);
    }
}
