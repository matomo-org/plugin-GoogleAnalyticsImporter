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
use Piwik\Plugins\GoogleAnalyticsImporter\ImporterGA4;
use Piwik\Plugins\VisitFrequency\API;
use Piwik\Plugins\GoogleAnalyticsImporter\Importers\VisitsSummary\RecordImporterGA4 as VisitsSummaryAPI;
class RecordImporterGA4 extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporterGA4
{
    const PLUGIN_NAME = 'VisitFrequency';
    public function importRecords(Date $day)
    {
        $filters = ['dimensionFilter' => ['dimension' => 'newVsReturning', 'filterType' => 'inList', 'filterValue' => ['(not set)', 'returning']]];
        $visitsSummaryRecordImporter = new VisitsSummaryAPI($this->getGaClient(), $this->getIdSite(), $this->getLogger(), null, $filters);
        $importer = StaticContainer::get(ImporterGA4::class);
        $importer->importDay(new \Piwik\Site($this->getIdSite()), $day, ['VisitsSummary' => $visitsSummaryRecordImporter], API::RETURNING_VISITOR_SEGMENT, 'VisitsSummary');
    }
}
