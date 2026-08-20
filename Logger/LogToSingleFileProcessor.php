<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\Logger;

use Monolog\LogRecord;
use Piwik\Log\Logger;
use Piwik\Container\StaticContainer;

class LogToSingleFileProcessor
{
    /**
     * @var string
     */
    public static $cliOutputPrefix = '';
    private static $logToSingleFileHandled = \false;
    /** @var int $idSite */
    private $idSite;
    public function __construct($idSite)
    {
        $this->idSite = (int) $idSite;
    }
    public function __invoke(LogRecord $record): LogRecord
    {
        $message = $record['message'];
        if (is_string($message)) {
            $record = $record->with(message: '(idSite: ' . $this->idSite . ') ' . $message);
        }
        return $record;
    }
    public static function handleLogToSingleFileInCliCommand($idSite)
    {
        if (self::$logToSingleFileHandled || empty($idSite)) {
            return;
        }
        self::$logToSingleFileHandled = \true;
        $logToSingleFile = StaticContainer::get('GoogleAnalyticsImporter.logToSingleFile');
        if ($logToSingleFile) {
            /** @var Logger $logger */
            $logger = StaticContainer::get(Logger::class);
            $logger->pushProcessor(new \Piwik\Plugins\GoogleAnalyticsImporter\Logger\LogToSingleFileProcessor($idSite));
            self::$cliOutputPrefix = '(idSite: ' . $idSite . ') ';
        }
    }
}
