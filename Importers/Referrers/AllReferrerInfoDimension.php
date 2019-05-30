<?php


namespace Piwik\Plugins\GoogleAnalyticsImporter\Importers\Referrers;


use Piwik\Plugins\Referrers\Columns\Base;
use Piwik\Tracker\Request;
use Piwik\Tracker\Visitor;

class AllReferrerInfoDimension extends Base
{
    public function getReferrerInformation($referrerUrl, $currentUrl, $idSite, Request $request, Visitor $visitor)
    {
        return parent::getReferrerInformation($referrerUrl, $currentUrl, $idSite, $request, $visitor);
    }
}