<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\Input;

use Piwik\Config;
use Piwik\Date;
use Piwik\SettingsServer;
class EndDate
{
    const CONFIG_NAME = 'forced_max_end_date';
    // value can be anything that works for Date::factory()
    /**
     * @internal tests only
     * @var null|string
     */
    public $forceMaxEndDate = null;
    public function __construct(Config $config)
    {
        $this->forceMaxEndDate = $this->readConfigForcedMaxEndDate($config);
    }
    public function getConfiguredMaxEndDate()
    {
        return $this->forceMaxEndDate;
    }
    /**
     * @return Date|null
     */
    public function getMaxEndDate()
    {
        // if Matomo for WordPress is used, then the data will be imported into the same site as the data is also being
        // tracked into by the sounds. So we need to make sure the import ends before Matomo for WordPress was installed
        // otherwise it would potentially always overwrite already aggregated report data
        if (method_exists(SettingsServer::class, 'isMatomoForWordPress') && SettingsServer::isMatomoForWordPress()) {
            $installDate = null;
            if (defined('\\WpMatomo\\Installer::OPTION_NAME_INSTALL_DATE')) {
                $installDate = get_option(\WpMatomo\Installer::OPTION_NAME_INSTALL_DATE);
            }
            try {
                $installDate = Date::factory($installDate);
            } catch (\Exception $ex) {
                // ignore
            }
            if (empty($installDate) || !$installDate instanceof Date) {
                // matomo for WordPress was installed before this option was set
                // we have to make sure there will be an end date otherwise it will always overwrite data
                // we assume it was installed 2 days ago. It's not 100% accurate but best we can do
                $installDate = Date::today()->subDay(2);
            } else {
                // import up to 1 day before original install
                $installDate = $installDate->subDay(1);
            }
            return $installDate;
        }
        if ($this->forceMaxEndDate) {
            try {
                return Date::factory($this->forceMaxEndDate);
            } catch (\Exception $ex) {
                return null;
            }
        }
        return null;
    }
    public function limitMaxEndDateIfNeeded($endDate)
    {
        $maxEndDate = $this->getMaxEndDate();
        // if Matomo for WordPress is used, then the data will be imported into the same site as the data is also being
        // tracked into by the sounds. So we need to make sure the import ends before Matomo for WordPress was installed
        // otherwise it would potentially always overwrite already aggregated report data
        if ($maxEndDate && (!$endDate || Date::factory($endDate)->isLater($maxEndDate))) {
            $endDate = $maxEndDate->toString();
        }
        return $endDate;
    }
    private function readConfigForcedMaxEndDate(Config $config)
    {
        if (empty($config)) {
            return null;
        }
        $configSection = $config->GoogleAnalyticsImporter;
        $maxEndDate = !empty($configSection[self::CONFIG_NAME]) ? $configSection[self::CONFIG_NAME] : null;
        if (!empty($maxEndDate)) {
            try {
                Date::factory($maxEndDate);
            } catch (\Exception $ex) {
                throw new \Exception("Invalid max end date: {$maxEndDate}");
            }
        }
        return $maxEndDate;
    }
}
