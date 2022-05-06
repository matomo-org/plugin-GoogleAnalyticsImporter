<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\Importers\VisitsSummary;


use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Piwik\Date;
use Piwik\Metrics;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleAnalyticsGA4QueryService;
use Psr\Log\LoggerInterface;

class RecordImporterGA4 extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporterGA4
{
    const PLUGIN_NAME = 'VisitsSummary';

    /**
     * @var array
     */
    private $numericRecords;

    /**
     * @var string
     */
    private $segmentToApply;

    public function __construct(GoogleAnalyticsGA4QueryService $gaQuery, $idSite, LoggerInterface $logger, $segmentToApply = null)
    {
        parent::__construct($gaQuery, $idSite, $logger);
        $this->segmentToApply = $segmentToApply;
    }

    public function importRecords(Date $day)
    {
        $gaQuery = $this->getGaClient();

        $options = [];
        if (!empty($this->segmentToApply)) {
            $options['segment'] = $this->segmentToApply;
        }

        $result = $gaQuery->query($day, [], $this->getVisitMetrics(), $options);

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