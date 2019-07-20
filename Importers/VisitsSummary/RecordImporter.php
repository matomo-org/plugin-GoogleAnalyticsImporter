<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\Importers\VisitsSummary;


use Piwik\Date;
use Piwik\Metrics;

class RecordImporter extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporter
{
    const PLUGIN_NAME = 'VisitsSummary';

    /**
     * @var array
     */
    private $numericRecords;

    public function importRecords(Date $day)
    {
        $gaQuery = $this->getGaQuery();
        $result = $gaQuery->query($day, [], $this->getVisitMetrics());

        $row = $result->getFirstRow();
        if (empty($row)) {
            $columns = [Metrics::INDEX_NB_VISITS => 0];
        } else {
            $columns = $row->getColumns();
        }

        $this->numericRecords = $columns;
        $this->insertNumericRecords($this->numericRecords);
    }

    public function getSessions()
    {
        return empty($this->numericRecords[Metrics::INDEX_NB_VISITS]) ? 0 : $this->numericRecords[Metrics::INDEX_NB_VISITS];
    }
}