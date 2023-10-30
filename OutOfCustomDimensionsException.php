<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter;

use Piwik\Piwik;
class OutOfCustomDimensionsException extends \Exception
{
    /**
     * OutOfCustomDimensionsException constructor.
     * @param int $requestedScopes
     * @param int $availableScopes
     */
    public function __construct($requestedScopeCount, $availableScopeCount, $scopeType)
    {
        parent::__construct(Piwik::translate('GoogleAnalyticsImporter_CannotImportMissingCustomDimensionSlots', [$scopeType, $requestedScopeCount, $availableScopeCount]));
    }
}
