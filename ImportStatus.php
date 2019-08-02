<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter;


use Piwik\Option;
use Piwik\Date;
use Piwik\Piwik;
use Piwik\Site;

// TODO: maybe make an import status entity class
class ImportStatus
{
    const OPTION_NAME_PREFIX = 'GoogleAnalyticsImporter.importStatus_';

    const STATUS_STARTED = 'started';
    const STATUS_ONGOING = 'ongoing';
    const STATUS_FINISHED = 'finished';
    const STATUS_ERRORED = 'errored';
    const STATUS_RATE_LIMITED = 'rate_limited';

    public function startingImport($propertyId, $accountId, $viewId, $idSite)
    {
        $now = Date::getNowTimestamp();
        $status = [
            'status' => self::STATUS_STARTED,
            'idSite' => $idSite,
            'ga' => [
                'property' => $propertyId,
                'account' => $accountId,
                'view' => $viewId,
            ],
            'last_date_imported' => null,
            'import_start_time' => $now,
            'import_end_time' => null,
            'last_job_start_time' => $now,
            'last_day_archived' => null,
            'import_range_start' => null,
            'import_range_end' => null,
        ];

        $this->saveStatus($status);
    }

    public function dayImportFinished($idSite, Date $date)
    {
        $status = $this->getImportStatus($idSite);
        $status['status'] = self::STATUS_ONGOING;

        if (empty($status['last_date_imported'])
            || !Date::factory($status['last_date_imported'])->isLater($date)
        ) {
            $status['last_date_imported'] = $date->toString();
        }

        $this->saveStatus($status);
    }

    public function setImportDateRange($idSite, Date $startDate = null, Date $endDate = null)
    {
        $status = $this->getImportStatus($idSite);
        if (!empty($startDate)) {
            $status['import_range_start'] = $startDate->toString();
        }
        if (!empty($endDate)) {
            $status['import_range_end'] = $endDate->toString();
        }
        $this->saveStatus($status);
    }

    public function resumeImport($idSite)
    {
        $status = $this->getImportStatus($idSite);
        $status['status'] = self::STATUS_ONGOING;
        $status['last_job_start_time'] = Date::getNowTimestamp();
        $this->saveStatus($status);
    }

    public function importArchiveFinished($idSite, Date $date)
    {
        $status = $this->getImportStatus($idSite);
        $status['last_day_archived'] = $date->toString();
        $this->saveStatus($status);
    }

    public function getImportStatus($idSite)
    {
        $optionName = $this->getOptionName($idSite);
        Option::clearCachedOption($optionName);
        $data = Option::get($optionName);
        if (empty($data)) {
            throw new \Exception("Import was cancelled.");
        }
        $data = json_decode($data, true);
        return $data;
    }

    public function finishedImport($idSite)
    {
        $status = $this->getImportStatus($idSite);
        $status['status'] = self::STATUS_FINISHED;
        $status['import_end_time'] = Date::getNowTimestamp();
        $this->saveStatus($status);
    }

    public function erroredImport($idSite, $errorMessage)
    {
        $status = $this->getImportStatus($idSite);
        $status['status'] = self::STATUS_ERRORED;
        $status['error'] = $errorMessage;
        $this->saveStatus($status);
    }

    public function rateLimitReached($idSite)
    {
        $status = $this->getImportStatus($idSite);
        $status['status'] = self::STATUS_RATE_LIMITED;
        $this->saveStatus($status);
    }

    public function getAllImportStatuses()
    {
        $optionValues = Option::getLike(self::OPTION_NAME_PREFIX . '%');

        $result = [];
        foreach ($optionValues as $optionValue) {
            $status = json_decode($optionValue, true);
            $status = $this->enrichStatus($status);
            $result[] = $status;
        }
        return $result;
    }

    public function deleteStatus($idSite)
    {
        $optionName = $this->getOptionName($idSite);
        Option::delete($optionName);
    }

    private function saveStatus($status)
    {
        $optionName = $this->getOptionName($status['idSite']);
        Option::set($optionName, json_encode($status));
    }

    private function getOptionName($idSite)
    {
        return self::OPTION_NAME_PREFIX . $idSite;
    }

    private function enrichStatus($status)
    {
        if (isset($status['idSite'])) {
            $status['site'] = new Site($status['idSite']);
        }

        if (isset($status['import_start_time'])) {
            $status['import_start_time'] = Date::factory($status['import_start_time'])->getDatetime();
        }

        if (isset($status['import_end_time'])) {
            $status['import_end_time'] = Date::factory($status['import_end_time'])->getDatetime();
        }

        if (isset($status['last_job_start_time'])) {
            $status['last_job_start_time'] = Date::factory($status['last_job_start_time'])->getDatetime();
        }

        if (isset($status['import_range_start'])) {
            $status['import_range_start'] = Date::factory($status['import_range_start'])->toString();
        }

        if (isset($status['import_range_end'])) {
            $status['import_range_end'] = Date::factory($status['import_range_end'])->toString();

            if (!empty($status['last_date_imported'])) {
                // TODO: unit test this, math doesn't seem right:
                $lastDateImported = Date::factory($status['last_date_imported']);
                $importEndDate = Date::factory($status['import_range_end']);

                $importStartTime = Date::factory($status['import_start_time']);

                if (isset($status['import_range_start'])) {
                    $importRangeStart = Date::factory($status['import_range_start']);
                } else {
                    $importRangeStart = Date::factory(Site::getCreationDateFor($status['idSite']));
                }

                $daysRunning = floor((Date::today()->getTimestamp() - $importStartTime->getTimestamp()) / 86400);
                $totalDaysLeft = floor(($importEndDate->getTimestamp() - $lastDateImported->getTimestamp()) / 86400);
                $totalDaysImported = floor(($lastDateImported->getTimestamp() - $importRangeStart->getTimestamp()) / 86400);

                $rateOfImport = $totalDaysImported / $daysRunning;
                $totalTimeLeftInDays = ceil($totalDaysLeft / $rateOfImport);

                $status['estimated_days_left_to_finish'] = max(0, $totalTimeLeftInDays);
            } else {
                $status['estimated_days_left_to_finish'] = Piwik::translate('General_Unknown');
            }
        }

        $status['gaInfoPretty'] = 'Property: ' . $status['ga']['property'] . "\nAccount: " . $status['ga']['account']
            . "\nView: " . $status['ga']['view'];

        return $status;
    }
}