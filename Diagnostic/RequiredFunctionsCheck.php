<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\Diagnostic;

use Piwik\Plugins\Diagnostics\Diagnostic\Diagnostic;
use Piwik\Plugins\Diagnostics\Diagnostic\DiagnosticResult;
use Piwik\Translation\Translator;
class RequiredFunctionsCheck implements Diagnostic
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
        $requiredFunctions = ['shell_exec', 'exec'];
        $areFunctionsPresent = \true;
        foreach ($requiredFunctions as $func) {
            if (!function_exists($func)) {
                $areFunctionsPresent = \false;
            }
        }
        $label = '[GoogleAnalyticsImporter] ' . $this->translator->translate('GoogleAnalyticsImporter_RequiredFunctionsCheck');
        if ($areFunctionsPresent) {
            return [DiagnosticResult::singleResult($label, DiagnosticResult::STATUS_OK)];
        } else {
            $explanation = $this->translator->translate('GoogleAnalyticsImporter_RequiredFunctionsMissing', [implode(', ', $requiredFunctions)]);
            return [DiagnosticResult::singleResult($label, DiagnosticResult::STATUS_ERROR, $explanation)];
        }
    }
}
