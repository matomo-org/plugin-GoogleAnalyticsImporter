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
class RecordImporterGA4 extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporterGA4
{
    const PLUGIN_NAME = 'UserLanguage';
    public function importRecords(Date $day)
    {
        $recordName = Archiver::LANGUAGE_RECORD_NAME;
        $record = new DataTable();
        $gaQuery = $this->getGaClient();
        $table = $gaQuery->query($day, ['languageCode'], $this->getConversionAwareVisitMetrics());
        foreach ($table->getRows() as $row) {
            $languageCode = $row->getMetadata('languageCode');
            if (empty($languageCode)) {
                $languageCode = self::NOT_SET_IN_GA_LABEL;
            }
            $this->addRowToTable($record, $row, $languageCode);
        }
        $this->insertRecord($recordName, $record);
        Common::destroy($record);
    }
}
