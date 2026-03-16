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
    const selectorNameInput = 'input[name="name"]';
    const selectorDescriptionInput = 'textarea[name="description"]';
    const selectorRedirectUrisInput = 'textarea[name="redirect_uris"]';

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

    async function sendFieldValue(selector, text)
    {
        await page.waitForSelector(selector, { visible: true });

        const field = await page.$(selector);
        if (!field) {
            throw new Error('Field not found for selector: ' + selector);
        }

        await page.evaluate((theSelector) => {
            const element = document.querySelector(theSelector);
            if (!element) {
                return;
            }

            element.value = '';
            element.dispatchEvent(new Event('input', { bubbles: true }));
            element.dispatchEvent(new Event('change', { bubbles: true }));
        }, selector);

        if (text) {
            await field.type(text);
        }

        await page.waitForTimeout(200);
    }

    async function selectValue(field, title)
    {
        await page.waitForSelector(field + ' input.select-dropdown', { visible: true });
        await page.evaluate((theField) => {
            $(theField + ' input.select-dropdown').click();
        }, field);
        await page.waitForTimeout(300);
        await page.evaluate((theField, theTitle) => {
            $(theField + ' .dropdown-content li:contains("' + theTitle + '"):first').click();
        }, field, title);
        await page.waitForTimeout(300);
    }

    async function submitForm()
    {
        await page.click('.oauth2-admin button[type="submit"]');
        await page.waitForNetworkIdle();
        await page.waitForTimeout(300);
    }

    async function fillClientForm(name, typeTitle, redirectUri)
    {
        await sendFieldValue(selectorNameInput, name);
        await sendFieldValue(selectorDescriptionInput, name + ' description');

        if (typeTitle) {
            await selectValue('div[name="type"]', typeTitle);
        }

        await selectValue('div[name="scopes"]', 'Matomo read level access.');
        await sendFieldValue(selectorRedirectUrisInput, redirectUri);
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
        await fillClientForm('Validation client', null, '');
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
        await fillClientForm('Public UI client', 'Public', 'https://public.example/callback');
        await submitForm();
        await capturePage('create_public_success');
    });

    it('should no longer show the client secret after reload', async function () {
        await page.reload();
        await capturePage('secret_not_shown_again');
    });
});
