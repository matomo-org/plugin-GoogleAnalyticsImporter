<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration\Importers\Actions;

use Piwik\Metrics;
use Piwik\Plugins\GoogleAnalyticsImporter\tests\Framework\BaseRecordImporterTest;
use Piwik\Plugins\MobileAppMeasurable\Type;
use Piwik\Tests\Framework\Fixture;
/**
 * @group GoogleAnalyticsImporter
 * @group GoogleAnalyticsImporter_Integration
 */
class RecordImporterTest extends BaseRecordImporterTest
{
    public function setUp() : void
    {
        parent::setUp();
        Fixture::createWebsite('2012-02-02 03:04:04', 1, 'mobile app', \false, 0, null, null, null, Type::ID);
    }
    public function getTestDir()
    {
        return __DIR__;
    }
    public function getTestedPluginName()
    {
        return 'Actions';
    }
    public function test_basicImport()
    {
        $this->runImporterTest('basicImport', [$this->makeMockPageUrlsResponse(), $this->makeMockPageUrlsVisitsRespone(), $this->makeMockPageTitlesResponse(), $this->makeMockPageTitlesVisitsResponse(), $this->makeMockEntryPageUrlsResponse(), $this->makeMockEntryPageTItlesResponse(), $this->makeMockExitPageUrlsResponse(), $this->makeMockExitPageTItlesResponse(), $this->makeMockSearchKeywordsResponse(), $this->makeMockSiteSearchCategoriesResponse()]);
    }
    private function makeMockPageUrlsResponse()
    {
        return [['ga:pagePath' => '/', Metrics::INDEX_PAGE_NB_HITS => 24, Metrics::INDEX_PAGE_SUM_TIME_SPENT => 10, Metrics::INDEX_PAGE_SUM_TIME_GENERATION => 3, Metrics::INDEX_PAGE_NB_HITS_WITH_TIME_GENERATION => 4], ['ga:pagePath' => '/index', Metrics::INDEX_PAGE_NB_HITS => 18, Metrics::INDEX_PAGE_SUM_TIME_SPENT => 10, Metrics::INDEX_PAGE_SUM_TIME_GENERATION => 3, Metrics::INDEX_PAGE_NB_HITS_WITH_TIME_GENERATION => 4], ['ga:pagePath' => '/folder/#ahashvalue', Metrics::INDEX_PAGE_NB_HITS => 12, Metrics::INDEX_PAGE_SUM_TIME_SPENT => 6, Metrics::INDEX_PAGE_SUM_TIME_GENERATION => 1, Metrics::INDEX_PAGE_NB_HITS_WITH_TIME_GENERATION => 1], ['ga:pagePath' => '/apage', Metrics::INDEX_PAGE_NB_HITS => 10, Metrics::INDEX_PAGE_SUM_TIME_SPENT => 5, Metrics::INDEX_PAGE_SUM_TIME_GENERATION => 2, Metrics::INDEX_PAGE_NB_HITS_WITH_TIME_GENERATION => 6], ['ga:pagePath' => '/folder/page', Metrics::INDEX_PAGE_NB_HITS => 9, Metrics::INDEX_PAGE_SUM_TIME_SPENT => 10, Metrics::INDEX_PAGE_SUM_TIME_GENERATION => 2, Metrics::INDEX_PAGE_NB_HITS_WITH_TIME_GENERATION => 1]];
    }
    private function makeMockPageUrlsVisitsRespone()
    {
        return [['ga:pagePath' => '/apage', Metrics::INDEX_NB_VISITS => 14, Metrics::INDEX_NB_UNIQ_VISITORS => 4], ['ga:pagePath' => '/folder/page', Metrics::INDEX_NB_VISITS => 7, Metrics::INDEX_NB_UNIQ_VISITORS => 7], ['ga:pagePath' => '/index', Metrics::INDEX_NB_VISITS => 6, Metrics::INDEX_NB_UNIQ_VISITORS => 5], ['ga:pagePath' => '/', Metrics::INDEX_NB_VISITS => 1, Metrics::INDEX_NB_UNIQ_VISITORS => 2]];
    }
    private function makeMockPageTitlesResponse()
    {
        return [['ga:pagePath' => '/', 'ga:pageTitle' => 'Index real', Metrics::INDEX_PAGE_NB_HITS => 19, Metrics::INDEX_PAGE_SUM_TIME_SPENT => 2, Metrics::INDEX_PAGE_SUM_TIME_GENERATION => 5, Metrics::INDEX_PAGE_NB_HITS_WITH_TIME_GENERATION => 7], ['ga:pagePath' => '/apage', 'ga:pageTitle' => 'A Page', Metrics::INDEX_PAGE_NB_HITS => 18, Metrics::INDEX_PAGE_SUM_TIME_SPENT => 1, Metrics::INDEX_PAGE_SUM_TIME_GENERATION => 2, Metrics::INDEX_PAGE_NB_HITS_WITH_TIME_GENERATION => 4], ['ga:pagePath' => '/index', 'ga:pageTitle' => 'Index page', Metrics::INDEX_PAGE_NB_HITS => 2, Metrics::INDEX_PAGE_SUM_TIME_SPENT => 2, Metrics::INDEX_PAGE_SUM_TIME_GENERATION => 3, Metrics::INDEX_PAGE_NB_HITS_WITH_TIME_GENERATION => 3], ['ga:pagePath' => '/folder/page', 'ga:pageTitle' => 'A Folder > Page', Metrics::INDEX_PAGE_NB_HITS => 1, Metrics::INDEX_PAGE_SUM_TIME_SPENT => 1, Metrics::INDEX_PAGE_SUM_TIME_GENERATION => 1, Metrics::INDEX_PAGE_NB_HITS_WITH_TIME_GENERATION => 1]];
    }
    private function makeMockPageTitlesVisitsResponse()
    {
        return [['ga:pageTitle' => 'A Page', Metrics::INDEX_NB_VISITS => 16, Metrics::INDEX_NB_UNIQ_VISITORS => 15], ['ga:pageTitle' => 'A Folder > Page', Metrics::INDEX_NB_VISITS => 20, Metrics::INDEX_NB_UNIQ_VISITORS => 10], ['ga:pageTitle' => 'Index real', Metrics::INDEX_NB_VISITS => 3, Metrics::INDEX_NB_UNIQ_VISITORS => 9], ['ga:pageTitle' => 'Index page', Metrics::INDEX_NB_VISITS => 14, Metrics::INDEX_NB_UNIQ_VISITORS => 7]];
    }
    private function makeMockEntryPageUrlsResponse()
    {
        return [['ga:landingPagePath' => '/index', Metrics::INDEX_PAGE_ENTRY_NB_VISITS => 8, Metrics::INDEX_PAGE_ENTRY_NB_ACTIONS => 9, Metrics::INDEX_PAGE_ENTRY_SUM_VISIT_LENGTH => 10, Metrics::INDEX_PAGE_ENTRY_BOUNCE_COUNT => 11], ['ga:landingPagePath' => '/', Metrics::INDEX_PAGE_ENTRY_NB_VISITS => 3, Metrics::INDEX_PAGE_ENTRY_NB_ACTIONS => 4, Metrics::INDEX_PAGE_ENTRY_SUM_VISIT_LENGTH => 5, Metrics::INDEX_PAGE_ENTRY_BOUNCE_COUNT => 6]];
    }
    private function makeMockEntryPageTItlesResponse()
    {
        return [['ga:landingPagePath' => '/index', 'ga:pageTitle' => 'Index page', Metrics::INDEX_PAGE_ENTRY_NB_VISITS => 8, Metrics::INDEX_PAGE_ENTRY_NB_ACTIONS => 9, Metrics::INDEX_PAGE_ENTRY_SUM_VISIT_LENGTH => 10, Metrics::INDEX_PAGE_ENTRY_BOUNCE_COUNT => 11], ['ga:landingPagePath' => '/index', 'ga:pageTitle' => 'Index page ', Metrics::INDEX_PAGE_ENTRY_NB_VISITS => 8, Metrics::INDEX_PAGE_ENTRY_NB_ACTIONS => 9, Metrics::INDEX_PAGE_ENTRY_SUM_VISIT_LENGTH => 10, Metrics::INDEX_PAGE_ENTRY_BOUNCE_COUNT => 11], ['ga:landingPagePath' => '/', 'ga:pageTitle' => 'Index real', Metrics::INDEX_PAGE_ENTRY_NB_VISITS => 3, Metrics::INDEX_PAGE_ENTRY_NB_ACTIONS => 4, Metrics::INDEX_PAGE_ENTRY_SUM_VISIT_LENGTH => 5, Metrics::INDEX_PAGE_ENTRY_BOUNCE_COUNT => 6], ['ga:landingPagePath' => '/?abc=1', 'ga:pageTitle' => 'Index real', Metrics::INDEX_PAGE_ENTRY_NB_VISITS => 1, Metrics::INDEX_PAGE_ENTRY_NB_ACTIONS => 1, Metrics::INDEX_PAGE_ENTRY_SUM_VISIT_LENGTH => 1, Metrics::INDEX_PAGE_ENTRY_BOUNCE_COUNT => 1]];
    }
    private function makeMockExitPageUrlsResponse()
    {
        return [['ga:landingPagePath' => '/', Metrics::INDEX_PAGE_EXIT_NB_UNIQ_VISITORS => 4, Metrics::INDEX_PAGE_EXIT_NB_VISITS => 4], ['ga:landingPagePath' => '/index', Metrics::INDEX_PAGE_EXIT_NB_UNIQ_VISITORS => 2, Metrics::INDEX_PAGE_EXIT_NB_VISITS => 2], ['ga:landingPagePath' => '/folder/page', Metrics::INDEX_PAGE_EXIT_NB_UNIQ_VISITORS => 1, Metrics::INDEX_PAGE_EXIT_NB_VISITS => 1]];
    }
    private function makeMockExitPageTItlesResponse()
    {
        return [['ga:landingPagePath' => '/', 'ga:pageTitle' => 'Index real', Metrics::INDEX_PAGE_EXIT_NB_VISITS => 4], ['ga:pageTitle' => 'Index page', 'ga:landingPagePath' => '/index', Metrics::INDEX_PAGE_EXIT_NB_VISITS => 2], ['ga:pageTitle' => 'A Folder > Page', 'ga:landingPagePath' => '/folder/page', Metrics::INDEX_PAGE_EXIT_NB_VISITS => 1], ['ga:pageTitle' => 'Index page', 'ga:landingPagePath' => '/index?a=1', Metrics::INDEX_PAGE_EXIT_NB_VISITS => 1]];
    }
    private function makeMockSearchKeywordsResponse()
    {
        return [['ga:searchKeyword' => 'serach 5', Metrics::INDEX_NB_VISITS => 10, Metrics::INDEX_NB_UNIQ_VISITORS => 4], ['ga:searchKeyword' => 'serach 6', Metrics::INDEX_NB_VISITS => 8, Metrics::INDEX_NB_UNIQ_VISITORS => 4], ['ga:searchKeyword' => 'serach 2', Metrics::INDEX_NB_VISITS => 1, Metrics::INDEX_NB_UNIQ_VISITORS => 1]];
    }
    public function test_mobileApp()
    {
        $this->runImporterTest('mobileApp', [$this->makeMockPageTitlesResponseForMobileApp(), $this->makeMockPageTitlesVisitsResponseForMobileApp(), $this->makeMockEntryPageTItlesResponseForMobileApp(), $this->makeMockExitPageTItlesResponseForMobileApp(), $this->makeMockSiteSearchCategoriesResponse()], $idSite = 2);
    }
    private function makeMockPageTitlesResponseForMobileApp()
    {
        return [['ga:screenName' => 'Homepage', Metrics::INDEX_PAGE_NB_HITS => 19, Metrics::INDEX_PAGE_SUM_TIME_SPENT => 2, Metrics::INDEX_PAGE_SUM_TIME_GENERATION => 5, Metrics::INDEX_PAGE_NB_HITS_WITH_TIME_GENERATION => 7], ['ga:screenName' => 'Homepage ', Metrics::INDEX_PAGE_NB_HITS => 19, Metrics::INDEX_PAGE_SUM_TIME_SPENT => 2, Metrics::INDEX_PAGE_SUM_TIME_GENERATION => 5, Metrics::INDEX_PAGE_NB_HITS_WITH_TIME_GENERATION => 7], ['ga:screenName' => 'A Screen', Metrics::INDEX_PAGE_NB_HITS => 18, Metrics::INDEX_PAGE_SUM_TIME_SPENT => 1, Metrics::INDEX_PAGE_SUM_TIME_GENERATION => 2, Metrics::INDEX_PAGE_NB_HITS_WITH_TIME_GENERATION => 4], ['ga:screenName' => 'Another Screen > Sub Screen', Metrics::INDEX_PAGE_NB_HITS => 2, Metrics::INDEX_PAGE_SUM_TIME_SPENT => 2, Metrics::INDEX_PAGE_SUM_TIME_GENERATION => 3, Metrics::INDEX_PAGE_NB_HITS_WITH_TIME_GENERATION => 3], ['ga:screenName' => 'Third Screen', Metrics::INDEX_PAGE_NB_HITS => 1, Metrics::INDEX_PAGE_SUM_TIME_SPENT => 1, Metrics::INDEX_PAGE_SUM_TIME_GENERATION => 1, Metrics::INDEX_PAGE_NB_HITS_WITH_TIME_GENERATION => 1]];
    }
    private function makeMockPageTitlesVisitsResponseForMobileApp()
    {
        return [['ga:screenName' => 'Homepage', Metrics::INDEX_NB_VISITS => 16, Metrics::INDEX_NB_UNIQ_VISITORS => 15], ['ga:screenName' => 'Homepage ', Metrics::INDEX_NB_VISITS => 16, Metrics::INDEX_NB_UNIQ_VISITORS => 15], ['ga:screenName' => 'A Screen', Metrics::INDEX_NB_VISITS => 20, Metrics::INDEX_NB_UNIQ_VISITORS => 10], ['ga:screenName' => 'Another Screen > Sub Screen', Metrics::INDEX_NB_VISITS => 3, Metrics::INDEX_NB_UNIQ_VISITORS => 9], ['ga:screenName' => 'Third Screen', Metrics::INDEX_NB_VISITS => 14, Metrics::INDEX_NB_UNIQ_VISITORS => 7]];
    }
    private function makeMockEntryPageTItlesResponseForMobileApp()
    {
        return [['ga:landingScreenName' => 'Homepage', Metrics::INDEX_PAGE_ENTRY_NB_UNIQ_VISITORS => 7, Metrics::INDEX_PAGE_ENTRY_NB_VISITS => 8, Metrics::INDEX_PAGE_ENTRY_NB_ACTIONS => 9, Metrics::INDEX_PAGE_ENTRY_SUM_VISIT_LENGTH => 10, Metrics::INDEX_PAGE_ENTRY_BOUNCE_COUNT => 11], ['ga:landingScreenName' => 'Homepage ', Metrics::INDEX_PAGE_ENTRY_NB_UNIQ_VISITORS => 7, Metrics::INDEX_PAGE_ENTRY_NB_VISITS => 8, Metrics::INDEX_PAGE_ENTRY_NB_ACTIONS => 9, Metrics::INDEX_PAGE_ENTRY_SUM_VISIT_LENGTH => 10, Metrics::INDEX_PAGE_ENTRY_BOUNCE_COUNT => 11], ['ga:landingScreenName' => 'Third Screen', Metrics::INDEX_PAGE_ENTRY_NB_UNIQ_VISITORS => 2, Metrics::INDEX_PAGE_ENTRY_NB_VISITS => 3, Metrics::INDEX_PAGE_ENTRY_NB_ACTIONS => 4, Metrics::INDEX_PAGE_ENTRY_SUM_VISIT_LENGTH => 5, Metrics::INDEX_PAGE_ENTRY_BOUNCE_COUNT => 6]];
    }
    private function makeMockExitPageTItlesResponseForMobileApp()
    {
        return [['ga:exitScreenName' => 'Homepage', Metrics::INDEX_PAGE_EXIT_NB_UNIQ_VISITORS => 4, Metrics::INDEX_PAGE_EXIT_NB_VISITS => 4], ['ga:exitScreenName' => 'Third Screen', Metrics::INDEX_PAGE_EXIT_NB_UNIQ_VISITORS => 2, Metrics::INDEX_PAGE_EXIT_NB_VISITS => 2], ['ga:exitScreenName' => 'Another Screen > Sub Screen', Metrics::INDEX_PAGE_EXIT_NB_UNIQ_VISITORS => 1, Metrics::INDEX_PAGE_EXIT_NB_VISITS => 1]];
    }
    private function makeMockSiteSearchCategoriesResponse()
    {
        return [['ga:searchCategory' => 'cat1', 'ga:searchUniques' => 5, 'ga:searchResultViews' => 6], ['ga:searchCategory' => 'cat2', 'ga:searchUniques' => 3, 'ga:searchResultViews' => 9], ['ga:searchCategory' => 'cat3', 'ga:searchUniques' => 10, 'ga:searchResultViews' => 20]];
    }
}
