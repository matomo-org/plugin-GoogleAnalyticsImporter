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
    public static function getMaxDailyApiQuota()
    {
        //Local installation. No limitations from google applicable
        return -1;
    }

    public static function getBalanceApiQuota()
    {
        return self::getMaxDailyApiQuota();
    }

    public static function saveApiUsed($numQueries)
    {
        //No need to save since it's a local installation
        return;
    }
}
