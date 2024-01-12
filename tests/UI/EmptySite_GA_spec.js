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

  this.fixture = "Piwik\\Tests\\Fixtures\\EmptySite";

  const generalParams = 'idSite=1&period=day&date=2010-01-03';

  before(function () {
    testEnvironment.detectedContentDetections = [];
    testEnvironment.connectedConsentManagers = [];
    testEnvironment.save();
  });

  after(function () {
    // unset all detections so fake class is no longer used
    delete testEnvironment.detectedContentDetections;
    delete testEnvironment.connectedConsentManagers;
    testEnvironment.save();
  });

  it('should show no data screen with GA import recommended', async function () {
    testEnvironment.detectedContentDetections = ['GoogleAnalytics3', 'Cloudflare'];
    testEnvironment.connectedConsentManagers = [];
    testEnvironment.save();

    const urlToTest = "?" + generalParams + "&module=CoreHome&action=index";
    await page.goto(urlToTest);

    const pageElement = await page.$('.page');
    expect(await pageElement.screenshot()).to.matchImage('list');
  });

  it('should show import details with ga3 offset banner', async function () {
    await page.evaluate(() => $('#start-tracking-detection a[href="#googleanalyticsimporter"]')[0].click());

    const pageElement = await page.$('.page');
    expect(await pageElement.screenshot()).to.matchImage('details_ga3');
  });

  it('should show no data screen with GA import recommended', async function () {
    testEnvironment.detectedContentDetections = ['GoogleAnalytics4', 'Cloudflare'];
    testEnvironment.connectedConsentManagers = [];
    testEnvironment.save();

    const urlToTest = "?" + generalParams + "&module=CoreHome&action=index";
    await page.goto(urlToTest);

    const pageElement = await page.$('.page');
    expect(await pageElement.screenshot()).to.matchImage('list');
  });

  it('should show import details', async function () {
    await page.evaluate(() => $('#start-tracking-detection a[href="#googleanalyticsimporter"]')[0].click());

    const pageElement = await page.$('.page');
    expect(await pageElement.screenshot()).to.matchImage('details_ga4');
  });

  it('should not show GA import recommended for admin user', async function () {
    testEnvironment.detectedContentDetections = ['GoogleAnalytics4', 'Cloudflare'];
    testEnvironment.connectedConsentManagers = [];
    testEnvironment.idSitesAdminAccess = [1,2,5];
    testEnvironment.save();

    const urlToTest = "?" + generalParams + "&module=CoreHome&action=index";
    await page.goto(urlToTest);

    const pageElement = await page.$('.page');
    expect(await pageElement.screenshot()).to.matchImage('list_admin');
  });
});
