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
    /**
     * @var \Google\Service\Analytics\CustomDimension
     */
    private $gaCustomDimension;
    private $reason;
    public function __construct(\Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\Analytics\CustomDimension $gaCustomDimension, $reason)
    {
        parent::__construct("Unable to import the '{$gaCustomDimension->getName()}' custom dimension: {$reason}.");
        $this->gaCustomDimension = $gaCustomDimension;
        $this->reason = $reason;
    }
}
