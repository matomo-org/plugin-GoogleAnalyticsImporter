<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration;

use Piwik\ArchiveProcessor\Parameters;
use Piwik\Option;
use Piwik\Period\Factory;
use Piwik\Plugin\Manager;
use Piwik\Plugins\GoogleAnalyticsImporter\GoogleAnalyticsImporter;
use Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus;
use Piwik\Segment;
use Piwik\Site;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
/**
 * @group GoogleAnalyticsImporter
 * @group GoogleAnalyticsImporter_Integration
 */
class GoogleAnalyticsImporterTest extends IntegrationTestCase
{
    public function setUp() : void
    {
        parent::setUp();
        Fixture::createWebsite('2015-01-02');
    }
    public function test_isRequestAuthorizedToArchive_doesNothingIfValueAlreadyFalse()
    {
        $plugin = $this->getPlugin();
        $isRequestAuthorizedToArchive = \false;
        $params = new Parameters(new Site(1), Factory::build('day', '2018-05-05'), new Segment('', [1]));
        $plugin->isRequestAuthorizedToArchive($isRequestAuthorizedToArchive, $params);
        $this->assertFalse($isRequestAuthorizedToArchive);
    }
    public function test_isRequestAuthorizedToArchive_doesNothingIfPeriodIsNotDay()
    {
        $plugin = $this->getPlugin();
        $isRequestAuthorizedToArchive = \true;
        $params = new Parameters(new Site(1), Factory::build('week', '2018-05-05'), new Segment('', [1]));
        $plugin->isRequestAuthorizedToArchive($isRequestAuthorizedToArchive, $params);
        $this->assertTrue($isRequestAuthorizedToArchive);
    }
    public function test_isRequestAuthorizedToArchive_doesNothingIfSiteHasNoImportedDateRange()
    {
        $plugin = $this->getPlugin();
        $isRequestAuthorizedToArchive = \true;
        $params = new Parameters(new Site(1), Factory::build('day', '2018-05-05'), new Segment('', [1]));
        $plugin->isRequestAuthorizedToArchive($isRequestAuthorizedToArchive, $params);
        $this->assertTrue($isRequestAuthorizedToArchive);
    }
    public function test_isRequestAuthorizedToArchive_doesNothingIfPeriodIsNotWithinImportedDateRange()
    {
        $plugin = $this->getPlugin();
        $isRequestAuthorizedToArchive = \true;
        $params = new Parameters(new Site(1), Factory::build('day', '2018-05-05'), new Segment('', [1]));
        $this->setImportedDateRange('2018-03-02', '2018-05-04');
        $plugin->isRequestAuthorizedToArchive($isRequestAuthorizedToArchive, $params);
        $this->assertTrue($isRequestAuthorizedToArchive);
    }
    public function test_isRequestAuthorizedToArchive_doesNothingIfThereIsLogDataWithinPeriodTimeframe()
    {
        $plugin = $this->getPlugin();
        $isRequestAuthorizedToArchive = \true;
        $params = new Parameters(new Site(1), Factory::build('day', '2018-05-05'), new Segment('', [1]));
        $this->setImportedDateRange('2018-03-02', '2018-05-10');
        $t = Fixture::getTracker($idSite = 1, '2018-05-05 12:04:05');
        $t->setUrl('http://example.com');
        Fixture::checkResponse($t->doTrackPageView('some title'));
        $plugin->isRequestAuthorizedToArchive($isRequestAuthorizedToArchive, $params);
        $this->assertTrue($isRequestAuthorizedToArchive);
    }
    public function test_isRequestAuthorizedToArchive_disablesArchivingIfPeriodIsInImportedDateRange_andNoVisitsExistForPeriod()
    {
        $plugin = $this->getPlugin();
        $isRequestAuthorizedToArchive = \true;
        $params = new Parameters(new Site(1), Factory::build('day', '2018-05-05'), new Segment('', [1]));
        $this->setImportedDateRange('2018-03-02', '2018-05-10');
        $plugin->isRequestAuthorizedToArchive($isRequestAuthorizedToArchive, $params);
        $this->assertFalse($isRequestAuthorizedToArchive);
    }
    protected static function configureFixture($fixture)
    {
        parent::configureFixture($fixture);
        $fixture->createSuperUser = \true;
    }
    /**
     * @return GoogleAnalyticsImporter
     */
    private function getPlugin()
    {
        return Manager::getInstance()->getLoadedPlugin('GoogleAnalyticsImporter');
    }
    private function setImportedDateRange(string $startDate, string $endDate)
    {
        $optionName = ImportStatus::IMPORTED_DATE_RANGE_PREFIX . 1;
        Option::set($optionName, $startDate . ',' . $endDate);
    }
}
