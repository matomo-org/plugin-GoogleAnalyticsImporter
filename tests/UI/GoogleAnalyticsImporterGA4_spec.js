/*!
 * Piwik - free/libre analytics platform
 *
 * GA importer tests
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

describe("GoogleAnalyticsImporterGA4", function () {
    this.timeout(0);

    this.fixture = 'Piwik\\Plugins\\GoogleAnalyticsImporter\\tests\\Fixtures\\MockApiResponsesGA4';

    var url = "?module=GoogleAnalyticsImporter&action=index&idSite=1&period=day&date=yesterday";

    async function removeStartResumeFinishTime() {
        await page.evaluate(() => $('td.import-start-finish-times').html(''));
    }

    async function updateStatusToStartedIfOnGoing() {
        await page.evaluate(() => {
            var status = $('.importStatusesTable tbody td.status:first').text();
            if (status && status.trim() === 'ongoing') {
                $('.importStatusesTable tbody td.status:first').html('started');
            }
        });
    }

    it("should load the settings correctly", async function () {
        await page.goto(url);

        const content = await page.$('.pageWrap');
        expect(await content.screenshot()).to.matchImage('load');
    });

    it("should load the settings correctly with GA4 option selected", async function () {
        await page.goto(url);

        const content = await page.$('.pageWrap');
        await page.evaluate(() => $('input:radio[name=selectedImporter]').val('ga4').change());
        expect(await content.screenshot()).to.matchImage('load_ga4');
    });

    it("should start an import properly", async function () {
        await page.evaluate(() => $('input#startDateGA4').val('2019-06-27').change());
        await page.evaluate(() => $('input#endDateGA4').val('2019-07-02').change());
        await page.evaluate(() => $('input#propertyIdGA4').val('properties/12345').change());
        await page.evaluate(() => $('div[name=extraCustomDimensionsGA4] input.control_text').val('userAgeBracket').change());
        await page.evaluate(() => $('div[name=extraCustomDimensionsGA4] select:eq(0)').val('string:visit').change());
        await page.click('[name=isVerboseLoggingEnabledGA4] label');

        await page.click('#startImportSubmitGA4');
        await page.waitForNetworkIdle();
        await page.waitForSelector('.pageWrap');

        await removeStartResumeFinishTime();
        await updateStatusToStartedIfOnGoing();

        const content = await page.$('.pageWrap');
        expect(await content.screenshot()).to.matchImage('start_import');
    });

    it('should show the error in the UI when an import fails', async function () {
        await page.waitForTimeout(70000);

        await page.reload({ timeout: 0 });
        await page.waitForSelector('.pageWrap');

        await removeStartResumeFinishTime();

        const content = await page.$('.pageWrap');
        expect(await content.screenshot()).to.matchImage('errored_import');
    });

    it('should manually resume an import when the resume button is clicked', async function () {
        await page.click('td.actions > a.icon-play');
        await page.waitForNetworkIdle();
        await page.waitForSelector('.pageWrap');

        await removeStartResumeFinishTime();

        const content = await page.$('.pageWrap');
        expect(await content.screenshot()).to.matchImage('resumed_import');
    });

    it('should schedule a re-import when the modal is used', async function () {
        await page.waitForTimeout(90000);

        await page.click('#reimport-date-range');

        await page.waitForSelector('#openScheduleReimportModal', { visible: true });
        await page.type('#re-import-start-date', '2022-06-02');
        await page.type('#re-import-end-date', '2022-06-02');

        await page.click('#scheduleReimportSubmit');
        await page.waitForNetworkIdle();
        await page.waitForSelector('.pageWrap');

        await removeStartResumeFinishTime();

        const content = await page.$('.pageWrap');
        expect(await content.screenshot()).to.matchImage('reimport_range');
    });

    it("should show that the import finished when the import finishes", async function () {
        let totalTime = 0;
        while (true) { // wait until import finishes
            await page.waitForTimeout(30000);

            await page.reload();
            await page.waitForSelector('.pageWrap');

            const elem = await page.$('td.actions > a.icon-delete');
            if (elem) {
                break;
            }

            console.log('waiting...');

            totalTime += 30;

            if (totalTime > 60 * 14) {
                throw new Error('timeout waiting for import to finish...');
            }
        }

        await removeStartResumeFinishTime();

        const content = await page.$('.pageWrap');
        expect(await content.screenshot()).to.matchImage('finished_import');
    });

    it('should remove the status when the trash icon is clicked', async function () {
        await page.click('td.actions > a.icon-delete');
        await page.waitForNetworkIdle();
        await page.waitForSelector('.pageWrap');

        await removeStartResumeFinishTime();

        const content = await page.$('.pageWrap');
        expect(await content.screenshot()).to.matchImage('removed_import');
    });

    it('should remove client configuration when the button is pressed', async function () {
        await page.click('#removeConfigForm button[type=submit]');
        await page.waitForNetworkIdle();
        await page.waitForSelector('.pageWrap');

        await removeStartResumeFinishTime();

        const content = await page.$('.pageWrap');
        expect(await content.screenshot()).to.matchImage('removed_client_config');
    });
});
