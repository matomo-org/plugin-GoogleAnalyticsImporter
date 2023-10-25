<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter;

class ImportWasCancelledException extends \Exception
{
    public function __construct()
    {
        parent::__construct("Import was cancelled.");
    }
}
