/*!
 * Matomo - free/libre analytics platform
 *
 * Screenshot integration tests.
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

describe("EmptySite_GA", function () {
    this.timeout(0);

    this.fixture = "Piwik\\Plugins\\GoogleAnalyticsImporter\\tests\\Fixtures\\EmptySiteWithSiteContentDetectionGA";

    const generalParams = 'idSite=1&period=day&date=2010-01-03';

    it('should show no data screen with GA import and with ga3 offset banner', async function () {
        const urlToTest = "?" + generalParams + "&module=CoreHome&action=index";
        await page.goto(urlToTest);

        const pageElement = await page.$('.page');
        expect(await pageElement.screenshot()).to.matchImage('emptySiteDashboard');
    });

});
