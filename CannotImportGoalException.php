<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter;

class CannotImportGoalException extends \Exception
{
    /**
     * CannotImportGoalException constructor.
     * @param string $reason
     */
    public function __construct(\Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\Analytics\Goal $gaGoal, $reason)
    {
        parent::__construct("Unable to import the '{$gaGoal->getName()}' goal: {$reason}.");
    }
}
