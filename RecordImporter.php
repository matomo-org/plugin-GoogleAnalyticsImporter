<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter;


use Piwik\DataAccess\ArchiveWriter;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Metrics;

abstract class RecordImporter
{
    /**
     * @var GoogleAnalyticsQueryService
     */
    private $gaQuery;

    /**
     * @var int
     */
    private $idSite;

    /**
     * @var ArchiveWriter
     */
    private $archiveWriter;

    public function __construct(GoogleAnalyticsQueryService $gaQuery, $idSite)
    {
        $this->gaQuery = $gaQuery;
        $this->idSite = $idSite;
    }

    public abstract function queryGoogleAnalyticsApi(Date $day); // TODO: rename to importRecords

    public function setArchiveWriter(ArchiveWriter $archiveWriter)
    {
        $this->archiveWriter = $archiveWriter;
    }

    protected function getGaQuery()
    {
        return $this->gaQuery;
    }

    protected function getVisitMetrics()
    {
        return [
            Metrics::INDEX_NB_UNIQ_VISITORS,
            Metrics::INDEX_NB_VISITS,
            Metrics::INDEX_NB_ACTIONS,
            Metrics::INDEX_SUM_VISIT_LENGTH,
            Metrics::INDEX_BOUNCE_COUNT,
            Metrics::INDEX_NB_VISITS_CONVERTED,
        ];
    }

    protected function getIdSite()
    {
        return $this->idSite;
    }

    protected function insertBlobRecord($name, $values)
    {
        $this->archiveWriter->insertBlobRecord($name, $values);
    }

    protected function insertNumericRecords(array $values)
    {
        foreach ($values as $name => $value) {
            if (is_numeric($name)) {
                $name = Metrics::getReadableColumnName($name);
            }
            $this->archiveWriter->insertRecord($name, $value);
        }
    }
    /*
    return array(
        Metrics::INDEX_MAX_ACTIONS                    => "max(" . self::LOG_VISIT_TABLE . ".visit_total_actions)",
        Metrics::INDEX_NB_USERS                       => "count(distinct " . self::LOG_VISIT_TABLE . ".user_id)",
    );
     */
}