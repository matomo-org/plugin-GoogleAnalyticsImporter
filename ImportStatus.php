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
use Piwik\Site;

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
        $status = [
            'status' => self::STATUS_STARTED,
            'idSite' => $idSite,
            'ga' => [
                'property' => $propertyId,
                'account' => $accountId,
                'view' => $viewId,
            ],
            'last_date_imported' => null,
            'import_start_time' => Date::getNowTimestamp(),
            'import_end_time' => null,
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

    public function getImportStatus($idSite)
    {
        $optionName = $this->getOptionName($idSite);
        $data = Option::get($optionName);
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

    public function getAllImportStatuses() // TODO: test
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

        $status['gaInfoPretty'] = 'Property: ' . $status['ga']['property'] . "\nAccount: " . $status['ga']['account']
            . "\nView: " . $status['ga']['view'];

        return $status;
    }
}