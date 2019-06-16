<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter;


class CannotImportCustomDimensionException extends \Exception
{
    public function __construct(\Google_Service_Analytics_CustomDimension $gaCustomDimension, $reason)
    {
        parent::__construct("Unable to import the '{$gaCustomDimension->getName()}' custom dimension: $reason.");
        $this->gaCustomDimension = $gaCustomDimension;
        $this->reason = $reason;
    }

}