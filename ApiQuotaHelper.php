<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter;

use Piwik\Config;
use Piwik\Piwik;
use Piwik\Settings\Storage\Backend\PluginSettingsTable;
use Piwik\Plugins\Billing\Ecommerce\Customer;
use Piwik\Plugins\Billing\Dao\Trial;

/**
 * Utility class with methods to manage Google Analytics Importer INI configuration.
 */
class ApiQuotaHelper
{
    public static $defaultConfig = array(
        'daily_api_quota' => 1000,
        'paid_ratio' => 0.7,
    );

    /**
     * Retrieve the defaul value from the $defaultConfig
     *
     * @param $optionName
     * @return mixed
     */
    public static function getDefaultConfigOptionValue($optionName)
    {
        return @self::$defaultConfig[$optionName];
    }

    /**
     * Calculate the daily maximum api quota available for the instance
     * @return int
     */
    public static function getMaxDailyApiQuota()
    {
        //Check if cloud instance
        if(!class_exists(Customer::class) && !class_exists(Trial::class)){
            //Not a cloud instance. No limits apply
            return -1;
        }

        //Cloud account. Check for import quota

        $dailyQuota = (isset(Config::getInstance()->GoogleAnalyticsImporter['daily_api_quota_per_customer'])) ?
            Config::getInstance()->GoogleAnalyticsImporter['daily_api_quota_per_customer'] :
            self::getDefaultConfigOptionValue('daily_api_quota');

        $quotaPaidRatio = (isset(Config::getInstance()->GoogleAnalyticsImporter['quota_api_paid_ratio'])) ?
            Config::getInstance()->GoogleAnalyticsImporter['quota_api_paid_ratio'] :
            self::getDefaultConfigOptionValue('paid_ratio');

        $trial = new Trial();
        $isInTrial = $trial->isInTrial();
        if($isInTrial){
            return (int)($dailyQuota * (1 - $quotaPaidRatio));
        }

        $customer = Customer::get();
        if($customer->getSubscriptionId()){
            //Paying Customer
            return (int)($dailyQuota * $quotaPaidRatio);
        }
        return 0;
    }

    public static function getPluginSettingsInstance()
    {
        return new PluginSettingsTable('GoogleAnalyticsImporter', Piwik::getCurrentUserLogin());
    }

    public static function getBalanceApiQuota()
    {
        $maxQuota = self::getMaxDailyApiQuota();
        if($maxQuota === -1){
            //Not a cloud customer. Return -1 reflect no limit
            return $maxQuota;
        }

        $pluginSettings = self::getPluginSettingsInstance()->load();

        //Can assume that import has never been run before
        if(empty($pluginSettings['lastImportDate'])){
            return $maxQuota;
        }

        if($pluginSettings['lastImportDate'] == date('Y-m-d')){
            if(empty($pluginSettings['importCountForTheDay'])){
                return $maxQuota;
            } else {
                return $maxQuota - $pluginSettings['importCountForTheDay'];
            }
        } else {
            self::saveApiUsed(0);
            return $maxQuota;
        }
    }

    public static function saveApiUsed($numQueries)
    {
        $pluginSettingsInstance = self::getPluginSettingsInstance();
        $pluginSettings = $pluginSettingsInstance->load();

        if(!array_key_exists('lastImportDate', $pluginSettings)){
            $pluginSettingsInstance->save([
                'lastImportDate' => date('Y-m-d'),
                'importCountForTheDay' => $numQueries
            ]);
            return;
        }

        if($pluginSettings['lastImportDate'] == date('Y-m-d')){
            $pluginSettingsInstance->save([
                'importCountForTheDay' => ($pluginSettings['importCountForTheDay'] += $numQueries),
                'lastImportDate' => date('Y-m-d')
            ]);
        } else {
            $pluginSettingsInstance->save([
                'lastImportDate' => date('Y-m-d'),
                'importCountForTheDay' => $numQueries
            ]);
        }
        return;
    }
}
