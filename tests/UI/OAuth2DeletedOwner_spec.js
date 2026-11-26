/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

describe("OAuth2DeletedOwner", function () {
    this.fixture = "Piwik\\Plugins\\OAuth2\\tests\\Fixtures\\OAuth2DeletedOwnerFixture";
    this.optionsOverride = {
        'persist-fixture-data': false
    };

    const adminUrl = '?module=OAuth2&action=index&idSite=1&period=day&date=2024-01-01';

    before(function () {
        testEnvironment.pluginsToLoad = ['OAuth2'];
        testEnvironment.save();
    });

    async function getClientRow(clientId)
    {
        await page.waitForSelector('.client-id-code', { visible: true });

        return page.evaluate(function (clientId) {
            const row = $('.client-id-code').filter(function () {
                return $(this).text().trim() === clientId;
            }).closest('tr');

            return {
                found: row.length === 1,
                status: row.find('td').eq(4).text().trim(),
                hasToggleButton: row.find('.icon-play, .icon-pause').length > 0,
                hasEditButton: row.find('.icon-edit').length > 0,
                hasDeleteButton: row.find('.icon-delete').length > 0,
            };
        }, clientId);
    }

    it('should list a client of a deleted owner as disabled, without a resume button', async function () {
        await page.goto(adminUrl);

        const row = await getClientRow('33333333333333333333333333333333');

        expect(row.found).to.be.true;
        expect(row.status).to.equal('Disabled, owner deleted');
        expect(row.hasToggleButton).to.be.false;
        expect(row.hasEditButton).to.be.true;
        expect(row.hasDeleteButton).to.be.true;
    });

    it('should still offer to pause an ordinary active client', async function () {
        await page.goto(adminUrl);

        const row = await getClientRow('44444444444444444444444444444444');

        expect(row.found).to.be.true;
        expect(row.status).to.equal('Active');
        expect(row.hasToggleButton).to.be.true;
    });
});
