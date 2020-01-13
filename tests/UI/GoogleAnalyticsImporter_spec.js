/*!
 * Piwik - free/libre analytics platform
 *
 * GA importer tests
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

describe("GoogleAnalyticsImporter", function () {
    this.fixture = 'Piwik\\Plugins\\GoogleAnalyticsImporter\\tests\\Fixtures\\MockApiResponses';

    var url = "?module=GoogleAnalyticsImporter&action=index&idSite=1&period=day&date=yesterday";

    async function removeStartResumeFinishTime() {
        await page.evaluate(() => $('td.import-start-time,td.import-end-time').html(''));
    }

    it("should load the settings correctly", async function () {
        await page.goto(url);

        const content = await page.$('#content');
        expect(await content.screenshot()).to.matchImage('load');
    });

    it("should start an import properly", async function () {
        await page.type('input#startDate', '2019-06-27');
        await page.type('input#endDate', '2019-07-02');
        await page.type('input#propertyId', 'UA-12345-6');
        await page.type('input#accountId', '12345');
        await page.type('input#viewId', '1234567');
        await page.type('input#extraCustomDimensions-p1-0', 'ga:networkLocation');
        await page.evaluate(() => $('[name=extraCustomDimensions-p2-0] select').val('string:visit').change());
        await page.click('[name=isVerboseLoggingEnabled] label');

        await page.click('#startImportSubmit');
        await page.waitForNetworkIdle();
        await page.waitFor('#content');

        await removeStartResumeFinishTime();

        const content = await page.$('#content');
        expect(await content.screenshot()).to.matchImage('start_import');
    });

    it('should show the error in the UI when an import fails', async function () {
        await page.waitFor(30000);

        await page.reload();
        await page.waitFor('#content');

        await removeStartResumeFinishTime();

        const content = await page.$('#content');
        expect(await content.screenshot()).to.matchImage('errored_import');
    });

    it('should manually resume an import when the resume button is clicked', async function () {
        await page.click('td.actions > a.icon-play');
        await page.waitForNetworkIdle();
        await page.waitFor('#content');

        await removeStartResumeFinishTime();

        const content = await page.$('#content');
        expect(await content.screenshot()).to.matchImage('resumed_import');
    });

    it("should show that the import finished when the import finishes", async function () {
        await page.waitFor(60000);

        await page.reload();
        await page.waitFor('#content');

        await removeStartResumeFinishTime();

        const content = await page.$('#content');
        expect(await content.screenshot()).to.matchImage('finished_import');
    });

    it('should remove the status when the trash icon is clicked', async function () {
        await page.click('td.actions > a.icon-delete');
        await page.waitForNetworkIdle();
        await page.waitFor('#content');

        await removeStartResumeFinishTime();

        const content = await page.$('#content');
        expect(await content.screenshot()).to.matchImage('removed_import');
    });

    it('should remove client configuration when the button is pressed', async function () {
        await page.click('#removeConfigForm button[type=submit]');
        await page.waitForNetworkIdle();
        await page.waitFor('#content');

        await removeStartResumeFinishTime();

        const content = await page.$('#content');
        expect(await content.screenshot()).to.matchImage('removed_client_config');
    });
});
