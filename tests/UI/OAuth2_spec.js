/*!
 * Matomo - free/libre analytics platform
 *
 * Screenshot integration tests.
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

describe("OAuth2Admin", function () {
    this.fixture = "Piwik\\Plugins\\OAuth2\\tests\\Fixtures\\OAuth2Fixture";
    this.optionsOverride = {
        'persist-fixture-data': false
    };

    const adminUrl = '?module=OAuth2&action=index&idSite=1&period=day&date=2024-01-01';
    const settingsUrl = '?module=CoreAdminHome&action=generalSettings#/OAuth2';

    before(function () {
        testEnvironment.pluginsToLoad = ['OAuth2'];
        testEnvironment.save();
    });

    async function capturePage(name)
    {
        await page.waitForNetworkIdle();
        await page.waitForTimeout(250);
        expect(await page.screenshotSelector('.pageWrap,#notificationContainer')).to.matchImage(name);
    }

    async function selectValue(page, field, title)
    {
        await page.evaluate(function(field) {
                $(field + ' input.select-dropdown').click()
            }, field);
        await page.waitForTimeout(800);
        await page.evaluate(function(field, title) {
                $(field + ' .dropdown-content li:contains("' + title + '"):first').click()
            }, field, title);
        await page.mouse.move(-10, -10);
        await page.mouse.click(-10, -10);
        await page.waitForTimeout(100);
    }

    async function submitForm()
    {
        await page.waitForSelector('.oauth2-admin form button.btn', { visible: true });
        await page.click('.oauth2-admin form button.btn');
        await page.waitForNetworkIdle();
        await page.waitForTimeout(300);
    }

    async function fillClientForm(name, typeTitle, redirectUri)
    {
        await page.evaluate(function (name) {
            $('#name').val(name).change();
        }, name);
        await page.evaluate(function (name) {
            $('#description').val(name + ' description').change();
        }, name);

        if (typeTitle) {
            await selectValue(page, 'div[name="type"]', typeTitle);
        }

        await selectValue(page,'div[name="scopes"]', 'Matomo read level access.');
        await page.evaluate(function (redirectUri) {
            $('#redirect_uris').val(redirectUri).change();
        }, redirectUri);
    }

    it('should show the OAuth2 system settings page', async function () {
        await page.goto(settingsUrl);
        await page.waitForNetworkIdle();
        await page.waitForTimeout(250);
        expect(await page.screenshotSelector('#OAuth2PluginSettings')).to.matchImage('system_settings');
    });

    it('should show the create client page', async function () {
        await page.goto(adminUrl);
        await capturePage('admin_page');
    });

    it('should validate the create client form', async function () {
        await page.goto(adminUrl);
        await submitForm();
        await capturePage('create_client_validation');
    });

    it('should create a confidential client and show the secret once', async function () {
        await page.goto(adminUrl);
        await fillClientForm('Confidential UI client', 'Confidential', 'https://confidential.example/callback');
        await submitForm();
        await capturePage('create_confidential_success');
    });

    it('should create a public client successfully', async function () {
        await page.goto(adminUrl);
        await fillClientForm('Public UI client', 'Public', 'https://public.example/callback');
        await submitForm();
        await capturePage('create_public_success');
    });

    it('should no longer show the client secret after reload', async function () {
        await page.reload();
        await capturePage('secret_not_shown_again');
    });
});
