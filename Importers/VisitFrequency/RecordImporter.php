<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\Importers\VisitFrequency;

use Piwik\Date;
use Piwik\Metrics;
use Piwik\Plugins\VisitFrequency\API;

class RecordImporter extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporter
{
    const PLUGIN_NAME = 'VisitFrequency';

    public function getArchiveWriterSegment()
    {
        return API::RETURNING_VISITOR_SEGMENT;
    }

    public function getArchiveWriterPluginName()
    {
        return 'VisitsSummary';
    }

    public function importRecords(Date $day)
    {
        $gaQuery = $this->getGaQuery();
        $result = $gaQuery->query($day, [], $this->getVisitMetrics(), [
            'segment' => [
                'segmentId' => 'gaid::-3',
            ],
        ]);

        $row = $result->getFirstRow();
        if (empty($row)) {
            $columns = [Metrics::INDEX_NB_VISITS => 0];
        } else {
            $columns = $row->getColumns();
        }

        $this->insertNumericRecords($columns);
    }
}