<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter;

class CannotImportCustomDimensionGA4Exception extends \Exception
{
    /**
     * @var \Google\Analytics\Admin\V1alpha\CustomDimension
     */
    private $gaCustomDimension;
    private $reason;
    public function __construct(\Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\CustomDimension $gaCustomDimension, $reason)
    {
        parent::__construct("Unable to import the '{$gaCustomDimension->getDisplayName()}' custom dimension: {$reason}.");
        $this->gaCustomDimension = $gaCustomDimension;
        $this->reason = $reason;
    }
}
