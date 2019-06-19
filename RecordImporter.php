<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter;

use Piwik\Config as PiwikConfig;
use Piwik\DataAccess\ArchiveWriter;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Metrics;
use Piwik\Tracker\Action;
use Psr\Log\LoggerInterface;

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

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $standardMaximumRows;

    public function __construct(GoogleAnalyticsQueryService $gaQuery, $idSite, LoggerInterface $logger)
    {
        $this->gaQuery = $gaQuery;
        $this->idSite = $idSite;
        $this->logger = $logger;
        $this->standardMaximumRows = PiwikConfig::getInstance()->General['datatable_archiving_maximum_rows_standard'];
        $this->logger = $logger;
    }

    public abstract function queryGoogleAnalyticsApi(Date $day); // TODO: rename to importRecords

    public function setArchiveWriter(ArchiveWriter $archiveWriter)
    {
        $this->archiveWriter = $archiveWriter;
    }

    /**
     * @return int
     */
    protected function getStandardMaximumRows()
    {
        return $this->standardMaximumRows;
    }

    protected function getGaQuery()
    {
        return $this->gaQuery;
    }

    protected function getLogger()
    {
        return $this->logger;
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

    protected function getConversionAwareVisitMetrics()
    {
        return array_merge($this->getVisitMetrics(), [
            Metrics::INDEX_NB_CONVERSIONS,
            Metrics::INDEX_REVENUE,
            Metrics::INDEX_GOALS,
        ]);
    }

    protected function getActionMetrics()
    {
        return [
            Metrics::INDEX_NB_VISITS,
            Metrics::INDEX_NB_UNIQ_VISITORS,
            Metrics::INDEX_PAGE_NB_HITS,
        ];
    }

    protected function getPageMetrics()
    {
        return array_merge($this->getActionMetrics(), [
            Metrics::INDEX_PAGE_SUM_TIME_GENERATION,
            Metrics::INDEX_PAGE_NB_HITS_WITH_TIME_GENERATION,

            // TODO: bandwidth could be supported via GA event
        ]);
    }

    protected function getEcommerceMetrics()
    {
        return [
            Metrics::INDEX_ECOMMERCE_ITEM_REVENUE,
            Metrics::INDEX_ECOMMERCE_ITEM_QUANTITY,
            Metrics::INDEX_ECOMMERCE_ITEM_PRICE,
            Metrics::INDEX_ECOMMERCE_ORDERS,
            Metrics::INDEX_NB_VISITS,
            // Metrics::INDEX_ECOMMERCE_ITEM_PRICE_VIEWED, TODO: should we support this? not sure it's possible in GA
        ];
    }

    protected function getConversionOnlyMetrics()
    {
        return [
            Metrics::INDEX_GOAL_NB_CONVERSIONS,
            Metrics::INDEX_GOAL_NB_VISITS_CONVERTED,
            Metrics::INDEX_GOAL_ECOMMERCE_REVENUE_SUBTOTAL,
            Metrics::INDEX_GOAL_ECOMMERCE_REVENUE_TAX,
            Metrics::INDEX_GOAL_ECOMMERCE_REVENUE_SHIPPING,
            Metrics::INDEX_GOAL_ECOMMERCE_ITEMS,
        ];
    }
/*
              => "count(*)",
                    => "count(distinct " . self::LOG_CONVERSION_TABLE . ".idvisit)",
                                => self::getSqlConversionRevenueSum(self::TOTAL_REVENUE_FIELD),
             => self::getSqlConversionRevenueSum(self::REVENUE_SUBTOTAL_FIELD),
                  => self::getSqlConversionRevenueSum(self::REVENUE_TAX_FIELD),
             => self::getSqlConversionRevenueSum(self::REVENUE_SHIPPING_FIELD),
             => self::getSqlConversionRevenueSum(self::REVENUE_DISCOUNT_FIELD),

 */
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

    protected function addRowToTable(DataTable $record, DataTable\Row $row, $newLabel)
    {
        $foundRow = $record->getRowFromLabel($newLabel);
        if (empty($foundRow)) {
            $foundRow = clone $row;
            $foundRow->deleteMetadata();
            $foundRow->setColumn('label', $newLabel);
            $record->addRow($foundRow);
        } else {
            $foundRow->sumRow($row);
        }
        return $foundRow;
    }

    protected function addRowToSubtable(DataTable\Row $topLevelRow, DataTable\Row $rowToAdd, $newLabel)
    {
        $subtable = $topLevelRow->getSubtable();
        if (!$subtable) {
            $subtable = new DataTable();
            $topLevelRow->setSubtable($subtable);
        }
        $this->addRowToTable($subtable, $rowToAdd, $newLabel);
    }
}