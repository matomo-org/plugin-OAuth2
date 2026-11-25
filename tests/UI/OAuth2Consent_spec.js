/*!
 * Matomo - free/libre analytics platform
 *
 * Screenshot integration tests for the OAuth2 consent screen.
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

describe("OAuth2Consent", function () {
    this.fixture = "Piwik\\Plugins\\OAuth2\\tests\\Fixtures\\OAuth2ConsentFixture";
    this.optionsOverride = {
        'persist-fixture-data': false
    };

    // must match the constants in OAuth2ConsentFixture
    const adminScopeClientId = '11111111111111111111111111111111';
    const readScopeClientId = '22222222222222222222222222222222';
    const redirectUri = 'https://client.example/callback';

    before(async function () {
        testEnvironment.pluginsToLoad = ['OAuth2'];
        // the consent screen is only shown to a user signed in to the browser session
        testEnvironment.testUseMockAuth = 0;
        testEnvironment.save();

        await page.goto('?module=Login');
        await page.waitForSelector('#login_form_login', { visible: true });
        await page.type('#login_form_login', superUserLogin);
        await page.type('#login_form_password', superUserPassword);
        await page.evaluate(function () {
            $('#login_form_submit').click();
        });
        await page.waitForNetworkIdle();
        await page.waitForSelector('.pageWrap');
    });

    after(function () {
        testEnvironment.testUseMockAuth = 1;
        testEnvironment.save();
    });

    function authorizeUrl(clientId, scope)
    {
        return '?module=OAuth2&action=authorize&response_type=code'
            + '&client_id=' + clientId
            + '&redirect_uri=' + encodeURIComponent(redirectUri)
            + '&scope=' + encodeURIComponent(scope)
            + '&state=uitest';
    }

    it('should show a scope radio group with the least privileged scope preselected', async function () {
        await page.goto(authorizeUrl(adminScopeClientId, 'matomo:read matomo:write matomo:admin'));
        await page.waitForSelector('.card-authorize', { visible: true });
        await page.waitForSelector('input[name="selected_scope"][value="matomo:admin"]', { visible: true });
        await page.waitForNetworkIdle();

        const radioValues = await page.$$eval('input[name="selected_scope"]', function (inputs) {
            return inputs.map(function (input) { return input.value; });
        });
        expect(radioValues).to.deep.equal(['matomo:read', 'matomo:write', 'matomo:admin']);

        const readIsChecked = await page.$eval('input[name="selected_scope"][value="matomo:read"]', function (input) {
            return input.checked;
        });
        expect(readIsChecked).to.equal(true);

        expect(await page.screenshotSelector('.card-authorize')).to.matchImage('consent_screen_multiple_scopes');
    });

    it('should show a single selectable scope without radio buttons', async function () {
        await page.goto(authorizeUrl(readScopeClientId, 'matomo:read matomo:write matomo:admin'));
        await page.waitForSelector('.card-authorize', { visible: true });
        // the only scope input is hidden here, so wait for the rendered scope instead
        await page.waitForSelector('.scope-list .scope', { visible: true });
        await page.waitForNetworkIdle();

        const radios = await page.$$('.card-authorize input[type="radio"]');
        expect(radios.length).to.equal(0);

        const selectedScope = await page.$eval('input[name="selected_scope"]', function (input) {
            return input.value;
        });
        expect(selectedScope).to.equal('matomo:read');

        expect(await page.screenshotSelector('.card-authorize')).to.matchImage('consent_screen_single_scope');
    });
});
