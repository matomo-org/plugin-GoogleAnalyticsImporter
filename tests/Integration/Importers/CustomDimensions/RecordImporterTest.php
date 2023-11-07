<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\Importers\CustomDimensions;

use Piwik\Container\StaticContainer;
use Piwik\Metrics;
use Piwik\Plugins\CustomDimensions\API;
use Piwik\Plugins\GoogleAnalyticsImporter\IdMapper;
use Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus;
use Piwik\Plugins\GoogleAnalyticsImporter\tests\Framework\BaseRecordImporterTest;
use Piwik\Plugins\MobileAppMeasurable\Type;
use Piwik\Tests\Framework\Fixture;
/**
 * @group GoogleAnalyticsImporter
 * @group GoogleAnalyticsImporter_Integration
 */
class RecordImporterTest extends BaseRecordImporterTest
{
    public function getTestDir()
    {
        return __DIR__;
    }
    public function getTestedPluginName()
    {
        return 'CustomDimensions';
    }
    public function setUp() : void
    {
        parent::setUp();
        $idMapper = StaticContainer::get(IdMapper::class);
        // set custom dimensions from GA
        $idDimension1 = API::getInstance()->configureNewCustomDimension($idSite = 1, 'ga dimension 1', 'visit', \true);
        $idMapper->mapEntityId('customdimension', '1', $idDimension1, 1);
        $idDimension2 = API::getInstance()->configureNewCustomDimension($idSite = 1, 'ga dimension 2', 'action', \true);
        $idMapper->mapEntityId('customdimension', '2', $idDimension2, 1);
        // create new site w/ extra custom dimensions
        Fixture::createWebsite('2012-03-04 00:00:00');
        API::getInstance()->configureNewCustomDimension($idSite = 2, 'ga:someDimension', 'visit', \true);
        API::getInstance()->configureNewCustomDimension($idSite = 2, 'ga:someOtherDimension', 'action', \true);
        // create mobile app measurable
        Fixture::createWebsite('2012-02-02 03:04:04', 1, 'mobile app', \false, 0, null, null, null, Type::ID);
        $idDimension1 = API::getInstance()->configureNewCustomDimension($idSite = 3, 'ga dimension 1', 'visit', \true);
        $idMapper->mapEntityId('customdimension', '1', $idDimension1, 3);
        API::getInstance()->configureNewCustomDimension($idSite = 3, 'ga:yetAnotherDim', 'action', \true);
        /** @var ImportStatus $importStatus */
        $importStatus = StaticContainer::get(ImportStatus::class);
        $importStatus->startingImport('someproperty', 'someaccount', 'someview', $idSite = 1);
        $importStatus->startingImport('someproperty', 'someaccount', 'someview', $idSite = 2, [['gaDimension' => 'ga:someDimension', 'dimensionScope' => 'visit'], ['gaDimension' => 'ga:someOtherDimension', 'dimensionScope' => 'action']]);
        $importStatus->startingImport('someproperty', 'someaccount', 'someview', $idSite = 3, [['gaDimension' => 'ga:yetAnotherDim', 'dimensionScope' => 'action']]);
    }
    public function test_basicImport()
    {
        $this->runImporterTest('basicImport', [$this->makeDim2ActionDimensionResponse(), $this->makeDim2ActionDimensionSecondResponse(), $this->makeDim2ActionDimensionThirdResponse(), $this->makeDim2ActionDimensionFourthResponse(), $this->makeDim1VisitDimensionResponse()]);
    }
    private function makeDim1VisitDimensionResponse()
    {
        return [['ga:dimension1' => 'value 1', Metrics::INDEX_NB_UNIQ_VISITORS => 4, Metrics::INDEX_NB_VISITS => 6, Metrics::INDEX_NB_ACTIONS => 3, Metrics::INDEX_SUM_VISIT_LENGTH => 2, Metrics::INDEX_BOUNCE_COUNT => 2, Metrics::INDEX_NB_VISITS_CONVERTED => 1, Metrics::INDEX_NB_CONVERSIONS => 1, Metrics::INDEX_REVENUE => 3, Metrics::INDEX_GOALS => []], ['ga:dimension1' => 'value 2', Metrics::INDEX_NB_UNIQ_VISITORS => 3, Metrics::INDEX_NB_VISITS => 3, Metrics::INDEX_NB_ACTIONS => 1, Metrics::INDEX_SUM_VISIT_LENGTH => 200, Metrics::INDEX_BOUNCE_COUNT => 0, Metrics::INDEX_NB_VISITS_CONVERTED => 1, Metrics::INDEX_NB_CONVERSIONS => 1, Metrics::INDEX_REVENUE => 1, Metrics::INDEX_GOALS => []]];
    }
    private function makeDim2ActionDimensionResponse()
    {
        return [['ga:dimension2' => 'v1', Metrics::INDEX_PAGE_NB_HITS => 10, Metrics::INDEX_PAGE_SUM_TIME_SPENT => 20, Metrics::INDEX_PAGE_SUM_TIME_GENERATION => 30, Metrics::INDEX_PAGE_NB_HITS_WITH_TIME_GENERATION => 40], ['ga:dimension2' => 'v2', Metrics::INDEX_PAGE_NB_HITS => 1, Metrics::INDEX_PAGE_SUM_TIME_SPENT => 2, Metrics::INDEX_PAGE_SUM_TIME_GENERATION => 3, Metrics::INDEX_PAGE_NB_HITS_WITH_TIME_GENERATION => 4]];
    }
    private function makeDim2ActionDimensionSecondResponse()
    {
        return [['ga:dimension2' => 'v1', Metrics::INDEX_NB_VISITS => 8, Metrics::INDEX_BOUNCE_COUNT => 3], ['ga:dimension2' => 'v2', Metrics::INDEX_NB_VISITS => 6, Metrics::INDEX_BOUNCE_COUNT => 3]];
    }
    private function makeDim2ActionDimensionThirdResponse()
    {
        return [['ga:dimension2' => 'v1', Metrics::INDEX_PAGE_EXIT_NB_VISITS => 6], ['ga:dimension2' => 'v2', Metrics::INDEX_PAGE_EXIT_NB_VISITS => 4]];
    }
    private function makeDim2ActionDimensionFourthResponse()
    {
        return [['ga:dimension2' => 'v1', Metrics::INDEX_PAGE_ENTRY_NB_VISITS => 9, Metrics::INDEX_PAGE_ENTRY_NB_ACTIONS => 3, Metrics::INDEX_PAGE_ENTRY_SUM_VISIT_LENGTH => 1], ['ga:dimension2' => 'v2', Metrics::INDEX_PAGE_ENTRY_NB_VISITS => 7, Metrics::INDEX_PAGE_ENTRY_NB_ACTIONS => 4, Metrics::INDEX_PAGE_ENTRY_SUM_VISIT_LENGTH => 4]];
    }
    public function test_extraCustomDimensions()
    {
        $this->runImporterTest('extraCustomDimensions', [$this->makeSomeOtherDimActionDimensionResponse(), $this->makeSomeOtherDimActionDimensionSecondResponse(), $this->makeSomeOtherDimActionDimensionThirdResponse(), $this->makeSomeOtherDimActionDimensionFourthResponse(), $this->makeSomeDimVisitDimensionResponse()], $idSite = 2);
    }
    private function makeSomeDimVisitDimensionResponse()
    {
        return [['ga:someDimension' => 'value 1', Metrics::INDEX_NB_UNIQ_VISITORS => 5, Metrics::INDEX_NB_VISITS => 5, Metrics::INDEX_NB_ACTIONS => 5, Metrics::INDEX_SUM_VISIT_LENGTH => 5, Metrics::INDEX_BOUNCE_COUNT => 5, Metrics::INDEX_NB_VISITS_CONVERTED => 5, Metrics::INDEX_NB_CONVERSIONS => 5, Metrics::INDEX_REVENUE => 5, Metrics::INDEX_GOALS => []], ['ga:someDimension' => 'value 2', Metrics::INDEX_NB_UNIQ_VISITORS => 3, Metrics::INDEX_NB_VISITS => 3, Metrics::INDEX_NB_ACTIONS => 3, Metrics::INDEX_SUM_VISIT_LENGTH => 3, Metrics::INDEX_BOUNCE_COUNT => 3, Metrics::INDEX_NB_VISITS_CONVERTED => 1, Metrics::INDEX_NB_CONVERSIONS => 1, Metrics::INDEX_REVENUE => 1, Metrics::INDEX_GOALS => []]];
    }
    private function makeSomeOtherDimActionDimensionResponse()
    {
        return [['ga:someOtherDimension' => 'v1', Metrics::INDEX_PAGE_NB_HITS => 8, Metrics::INDEX_PAGE_SUM_TIME_SPENT => 8, Metrics::INDEX_PAGE_SUM_TIME_GENERATION => 8, Metrics::INDEX_PAGE_NB_HITS_WITH_TIME_GENERATION => 8], ['ga:someOtherDimension' => 'v2', Metrics::INDEX_PAGE_NB_HITS => 4, Metrics::INDEX_PAGE_SUM_TIME_SPENT => 4, Metrics::INDEX_PAGE_SUM_TIME_GENERATION => 4, Metrics::INDEX_PAGE_NB_HITS_WITH_TIME_GENERATION => 4]];
    }
    private function makeSomeOtherDimActionDimensionSecondResponse()
    {
        return [['ga:someOtherDimension' => 'v1', Metrics::INDEX_NB_VISITS => 9, Metrics::INDEX_BOUNCE_COUNT => 7], ['ga:someOtherDimension' => 'v2', Metrics::INDEX_NB_VISITS => 6, Metrics::INDEX_BOUNCE_COUNT => 5]];
    }
    private function makeSomeOtherDimActionDimensionThirdResponse()
    {
        return [['ga:someOtherDimension' => 'v1', Metrics::INDEX_PAGE_EXIT_NB_VISITS => 5], ['ga:someOtherDimension' => 'v2', Metrics::INDEX_PAGE_EXIT_NB_VISITS => 2]];
    }
    private function makeSomeOtherDimActionDimensionFourthResponse()
    {
        return [['ga:someOtherDimension' => 'v1', Metrics::INDEX_PAGE_ENTRY_NB_VISITS => 7, Metrics::INDEX_PAGE_ENTRY_NB_ACTIONS => 6, Metrics::INDEX_PAGE_ENTRY_SUM_VISIT_LENGTH => 5], ['ga:someOtherDimension' => 'v2', Metrics::INDEX_PAGE_ENTRY_NB_VISITS => 4, Metrics::INDEX_PAGE_ENTRY_NB_ACTIONS => 3, Metrics::INDEX_PAGE_ENTRY_SUM_VISIT_LENGTH => 2]];
    }
    public function test_mobileApp()
    {
        $this->runImporterTest('mobileApp', [$this->makeYetAnotherDimActionDimensionResponse(), $this->makeYetAnotherDimActionDimensionSecondResponse(), $this->makeYetAnotherDimActionDimensionThirdResponse(), $this->makeYetAnotherDimActionDimensionFourthResponse(), $this->makeDim1VisitDimensionResponseForMobile()], $idSite = 3);
    }
    private function makeDim1VisitDimensionResponseForMobile()
    {
        return [['ga:dimension1' => 'value 1', Metrics::INDEX_NB_UNIQ_VISITORS => 8, Metrics::INDEX_NB_VISITS => 7, Metrics::INDEX_NB_ACTIONS => 6, Metrics::INDEX_SUM_VISIT_LENGTH => 5, Metrics::INDEX_BOUNCE_COUNT => 4, Metrics::INDEX_NB_VISITS_CONVERTED => 3, Metrics::INDEX_NB_CONVERSIONS => 2, Metrics::INDEX_REVENUE => 1, Metrics::INDEX_GOALS => []], ['ga:dimension1' => 'value 2', Metrics::INDEX_NB_UNIQ_VISITORS => 6, Metrics::INDEX_NB_VISITS => 5, Metrics::INDEX_NB_ACTIONS => 4, Metrics::INDEX_SUM_VISIT_LENGTH => 3, Metrics::INDEX_BOUNCE_COUNT => 2, Metrics::INDEX_NB_VISITS_CONVERTED => 1, Metrics::INDEX_NB_CONVERSIONS => 1, Metrics::INDEX_REVENUE => 1, Metrics::INDEX_GOALS => []]];
    }
    private function makeYetAnotherDimActionDimensionResponse()
    {
        return [['ga:yetAnotherDim' => 'v1', Metrics::INDEX_PAGE_NB_HITS => 10, Metrics::INDEX_PAGE_SUM_TIME_SPENT => 10, Metrics::INDEX_PAGE_SUM_TIME_GENERATION => 8, Metrics::INDEX_PAGE_NB_HITS_WITH_TIME_GENERATION => 8], ['ga:yetAnotherDim' => 'v2', Metrics::INDEX_PAGE_NB_HITS => 6, Metrics::INDEX_PAGE_SUM_TIME_SPENT => 6, Metrics::INDEX_PAGE_SUM_TIME_GENERATION => 5, Metrics::INDEX_PAGE_NB_HITS_WITH_TIME_GENERATION => 5]];
    }
    private function makeYetAnotherDimActionDimensionSecondResponse()
    {
        return [['ga:yetAnotherDim' => 'v1', Metrics::INDEX_NB_VISITS => 19, Metrics::INDEX_BOUNCE_COUNT => 17], ['ga:yetAnotherDim' => 'v2', Metrics::INDEX_NB_VISITS => 6, Metrics::INDEX_BOUNCE_COUNT => 5]];
    }
    private function makeYetAnotherDimActionDimensionThirdResponse()
    {
        return [['ga:yetAnotherDim' => 'v1', Metrics::INDEX_PAGE_EXIT_NB_VISITS => 25], ['ga:yetAnotherDim' => 'v2', Metrics::INDEX_PAGE_EXIT_NB_VISITS => 12]];
    }
    private function makeYetAnotherDimActionDimensionFourthResponse()
    {
        return [['ga:yetAnotherDim' => 'v1', Metrics::INDEX_PAGE_ENTRY_NB_VISITS => 10, Metrics::INDEX_PAGE_ENTRY_NB_ACTIONS => 7, Metrics::INDEX_PAGE_ENTRY_SUM_VISIT_LENGTH => 4], ['ga:yetAnotherDim' => 'v2', Metrics::INDEX_PAGE_ENTRY_NB_VISITS => 7, Metrics::INDEX_PAGE_ENTRY_NB_ACTIONS => 2, Metrics::INDEX_PAGE_ENTRY_SUM_VISIT_LENGTH => 2]];
    }
}
