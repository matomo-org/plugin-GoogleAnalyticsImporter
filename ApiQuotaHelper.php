<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter;

/**
 * Utility class with methods to manage Google Analytics Importer INI configuration.
 */
class ApiQuotaHelper
{
    /**
     * Calculate the daily maximum api quota available for the instance
     * @return int
     */
    public function getMaxDailyApiQuota(): int
    {
        //Local installation. No limitations from google applicable
        return -1;
    }
    public function getBalanceApiQuota()
    {
        return $this->getMaxDailyApiQuota();
    }
    public function saveApiUsed($numQueries)
    {
        //No need to save since it's a local installation
        return;
    }
    public function trackEvent($event, $name)
    {
        //DI to takeover on ConnectAccount
        return;
    }
    public function getImportCountForTheDay()
    {
        //Local installation. No limitations from google applicable
        return -1;
    }
}
