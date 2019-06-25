<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\Importers\Resolution;


use Piwik\Common;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Metrics;
use Piwik\Plugins\Resolution\Archiver;

class RecordImporter extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporter
{
    const PLUGIN_NAME = 'Resolution';

    public function queryGoogleAnalyticsApi(Date $day)
    {
        $record = new DataTable();

        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, $dimension = ['ga:screenResolution'], $this->getConversionAwareVisitMetrics());
        foreach ($table->getRows() as $row) {
            $label = $row->getMetadata('ga:screenResolution');
            if (empty($label)) {
                $label = '(not set)';
            }
            $this->addRowToTable($record, $row, $label);
        }
        Common::destroy($table);

        $blob = $record->getSerialized($this->getStandardMaximumRows(), null, Metrics::INDEX_NB_VISITS);
        $this->insertBlobRecord(Archiver::RESOLUTION_RECORD_NAME, $blob);
        unset($blob);
        Common::destroy($record);
    }
}