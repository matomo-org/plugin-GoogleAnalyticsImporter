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
     * @param \Google_Service_Analytics_Goal $gaGoal
     * @param string $string
     */
    public function __construct(\Google_Service_Analytics_Goal $gaGoal, $reason)
    {
        parent::__construct("Unable to import the '{$gaGoal->getName()}' goal: $reason.");
        $this->gaGoal = $gaGoal;
        $this->reason = $reason;
    }
}