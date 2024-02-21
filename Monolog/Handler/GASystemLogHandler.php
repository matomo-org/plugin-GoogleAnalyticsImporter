<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\Monolog\Handler;

/**
 * A class to use the SyslogHandler instead of including the dependency directly
 */

// Need to do this, so that we don't have to increase Matomo min version to 5.1.0 atleast

if (class_exists('Piwik\Plugins\Monolog\Handler\SyslogHandler')) {
    class GASystemLogHandler extends \Piwik\Plugins\Monolog\Handler\SyslogHandler
    {
    }
} else {
    class GASystemLogHandler extends \Monolog\Handler\SyslogHandler
    {
    }
}

