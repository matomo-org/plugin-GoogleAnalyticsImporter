<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter;

use Piwik\Container\StaticContainer;
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
            'CronArchive.getIdSitesNotUsingTracker' => 'getIdSitesNotUsingTracker',
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

    public function getIdSitesNotUsingTracker(&$idSitesNotUsingTracker)
    {
        /** @var LoggerInterface $logger */
        $logger = StaticContainer::get(LoggerInterface::class);

        /** @var ImportStatus $importStatus */
        $importStatus = StaticContainer::get(ImportStatus::class);
        $allStatuses = $importStatus->getAllImportStatuses();
        foreach ($allStatuses as $status) {
            $idSite = $status['idSite'];

            if ($status['status'] == ImportStatus::STATUS_FINISHED) {
                $optionName = $this->getArchivingFinishedOptionName($idSite);

                $archivingFinished = Option::get($optionName);
                if ($archivingFinished) {
                    continue;
                }
            }

            $logger->info("Site {idSite} has imported data from Google but has not archived yet, adding to list to archive.", [
                'idSite' => $idSite,
            ]);

            $idSitesNotUsingTracker[] = $idSite;
        }
    }

    public function archivingFinishedForSite($idSite, $completed)
    {
        /** @var LoggerInterface $logger */
        $logger = StaticContainer::get(LoggerInterface::class);

        if (!$completed) {
            $logger->info("Archiving for imported site (ID = {idSite}) was not completed successfully. Will try again next run.", [
                'idSite' => $idSite,
            ]);
            return;
        }

        /** @var ImportStatus $importStatus */
        $importStatus = StaticContainer::get(ImportStatus::class);
        foreach ($importStatus->getAllImportStatuses() as $importStatus) {
            if ($importStatus['idSite'] != $idSite) {
                continue;
            }

            $optionName = $this->getArchivingFinishedOptionName($idSite);
            Option::set($optionName, '1');

            return;
        }
    }

    private function getArchivingFinishedOptionName($idSite)
    {
        return self::OPTION_ARCHIVING_FINISHED_FOR_SITE_PREFIX . $idSite;
    }
}
