<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter;

class ImportConfiguration
{
    /**
     * @var int
     */
    private $numCustomVariables;
    /**
     * @return int
     */
    public function getNumCustomVariables()
    {
        return $this->numCustomVariables;
    }
    /**
     * @param int $numCustomVariables
     */
    public function setNumCustomVariables($numCustomVariables)
    {
        $this->numCustomVariables = $numCustomVariables;
    }
}
