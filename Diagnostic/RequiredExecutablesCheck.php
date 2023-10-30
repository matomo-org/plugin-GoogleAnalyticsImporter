<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\Diagnostic;

use Piwik\CliMulti\CliPhp;
use Piwik\Plugins\Diagnostics\Diagnostic\Diagnostic;
use Piwik\Plugins\Diagnostics\Diagnostic\DiagnosticResult;
use Piwik\SettingsServer;
use Piwik\Translation\Translator;
class RequiredExecutablesCheck implements Diagnostic
{
    /**
     * @var Translator
     */
    private $translator;
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }
    /**
     * @return DiagnosticResult[]
     */
    public function execute()
    {
        $results = [];
        $baseLabel = '[GoogleAnalyticsImporter] ' . $this->translator->translate('GoogleAnalyticsImporter_RequiredExecutablesCheck');
        $cliPhp = new CliPhp();
        $phpBinary = $cliPhp->findPhpBinary();
        $label = $baseLabel . ' (php)';
        if (empty($phpBinary)) {
            $explanation = $this->translator->translate('GoogleAnalyticsImporter_PhpExecutableMissing');
            $results[] = DiagnosticResult::singleResult($label, DiagnosticResult::STATUS_ERROR, $explanation);
        } else {
            $results[] = DiagnosticResult::singleResult($label, DiagnosticResult::STATUS_OK);
        }
        if (!SettingsServer::isWindows()) {
            $isNohupPresent = $this->isNohupPresent();
            $label = $baseLabel . ' (nohup)';
            if ($isNohupPresent) {
                $results[] = DiagnosticResult::singleResult($label, DiagnosticResult::STATUS_OK);
            } else {
                $explanation = $this->translator->translate('GoogleAnalyticsImporter_NohupExecutableMissing');
                $results[] = DiagnosticResult::singleResult($label, DiagnosticResult::STATUS_ERROR, $explanation);
            }
        }
        return $results;
    }
    public function isNohupPresent()
    {
        return !empty(@shell_exec('command -v nohup'));
    }
}
