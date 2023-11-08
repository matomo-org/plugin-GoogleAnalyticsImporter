<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\Importers\VisitFrequency;

use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Plugins\GoogleAnalyticsImporter\Importer;
use Piwik\Plugins\VisitFrequency\API;
use Piwik\Plugins\GoogleAnalyticsImporter\Importers\VisitsSummary\RecordImporter as VisitsSummaryAPI;
class RecordImporter extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporter
{
    const PLUGIN_NAME = 'VisitFrequency';
    public function importRecords(Date $day)
    {
        $segmentToApply = ['segmentId' => 'gaid::-3'];
        $visitsSummaryRecordImporter = new VisitsSummaryAPI($this->getGaQuery(), $this->getIdSite(), $this->getLogger(), $segmentToApply);
        $importer = StaticContainer::get(Importer::class);
        $importer->importDay(new \Piwik\Site($this->getIdSite()), $day, ['VisitsSummary' => $visitsSummaryRecordImporter], API::RETURNING_VISITOR_SEGMENT, 'VisitsSummary');
    }
}
