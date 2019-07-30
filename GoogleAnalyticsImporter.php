<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter;

use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Option;
use Psr\Log\LoggerInterface;

class GoogleAnalyticsImporter extends \Piwik\Plugin
{
    const OPTION_ARCHIVING_FINISHED_FOR_SITE_PREFIX = 'GoogleAnalyticsImporter.archivingFinished.';

    public function getListHooksRegistered()
    {
        return [
            'AssetManager.getJavaScriptFiles'        => 'getJsFiles',
            'AssetManager.getStylesheetFiles'        => 'getStylesheetFiles',
            'CronArchive.archiveSingleSite.finish' => 'archivingFinishedForSite',
        ];
    }

    public function getJsFiles(&$jsFiles)
    {
        $jsFiles[] = "plugins/GoogleAnalyticsImporter/angularjs/import-status/import-status.controller.js";
        $jsFiles[] = "plugins/GoogleAnalyticsImporter/angularjs/import-scheduler/import-scheduler.controller.js";
    }

    public function getStylesheetFiles(&$stylesheets)
    {
        $stylesheets[] = "plugins/GoogleAnalyticsImporter/stylesheets/styles.less";
    }

    public function archivingFinishedForSite($idSite, $completed)
    {
        /** @var LoggerInterface $logger */
        $logger = StaticContainer::get(LoggerInterface::class);

        /** @var ImportStatus $importStatus */
        $importStatus = StaticContainer::get(ImportStatus::class);

        try {
            $importStatus->getImportStatus($idSite);
        } catch (\Exception $ex) { // TODO: use specific exception
            return;
        }

        $dateFinished = getenv(Tasks::DATE_FINISHED_ENV_VAR);
        if (empty($dateFinished)) {
            $logger->debug('Archiving for imported site was finished, but date environment variable not set. Cannot mark day as complete.');
            return;
        }

        $dateFinished = Date::factory($dateFinished);

        if (!$completed) {
            $logger->info("Archiving for imported site (ID = {idSite}) was not completed successfully. Will try again next run.", [
                'idSite' => $idSite,
            ]);
            return;
        }

        $importStatus->importArchiveFinished($idSite, $dateFinished);
    }
}
