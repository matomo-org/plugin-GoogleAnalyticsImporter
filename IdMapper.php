<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter;

use Piwik\Option;
class IdMapper
{
    const OPTION_PREFIX = 'GoogleAnalytics.idMapping.';
    public function mapEntityId($type, $gaEntiyId, $entiyId, $idSite)
    {
        $optionName = $this->getOptionName($type, $entiyId, $idSite);
        Option::set($optionName, $gaEntiyId);
    }
    public function getGoogleAnalyticsId($type, $entityId, $idSite)
    {
        $optionName = $this->getOptionName($type, $entityId, $idSite);
        $result = Option::get($optionName);
        if ($result === \false) {
            return null;
        }
        return $result;
    }
    private function getOptionName($type, $entityId, $idSite)
    {
        return self::OPTION_PREFIX . $type . '.' . $entityId . '.' . $idSite;
    }
}
