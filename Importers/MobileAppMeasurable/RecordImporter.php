<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\Importers\MobileAppMeasurable;


use Piwik\Date;
use Piwik\Plugins\MobileAppMeasurable\Type;
use Piwik\Site;

class RecordImporter extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporter
{
    public function supportsSite()
    {
        return Site::getTypeFor($this->getIdSite()) == Type::ID;
    }

    public function importRecords(Date $day)
    {
        // TODO: Implement importRecords() method.
    }
}