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

    public function mapEntityId($type, $gaEntiyId, $entiyId)
    {
        $optionName = $this->getOptionName($type, $entiyId);
        Option::set($optionName, $gaEntiyId);
    }

    public function getGoogleAnalyticsId($type, $entityId)
    {
        $optionName = $this->getOptionName($type, $entityId);
        $result = Option::get($optionName);
        if ($result === false) {
            return null;
        }
        return $result;
    }

    private function getOptionName($type, $entiyId)
    {
        return self::OPTION_PREFIX . $type . '.' . $entiyId;
    }
}