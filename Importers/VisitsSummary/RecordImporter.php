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

class RecordImporter extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporter
{
    const PLUGIN_NAME = 'VisitsSummary';

    public function importRecords(Date $day)
    {
        $gaQuery = $this->getGaQuery();
        $result = $gaQuery->query($day, [], $this->getVisitMetrics());
        $this->insertNumericRecords($result->getFirstRow()->getColumns());
    }
}