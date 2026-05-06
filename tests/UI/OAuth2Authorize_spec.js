/*!
 * Matomo - free/libre analytics platform
 *
 * Screenshot integration tests.
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

describe("OAuth2Authorize", function () {
    this.fixture = "Piwik\\Plugins\\OAuth2\\tests\\Fixtures\\OAuth2AuthorizeFixture";
    this.optionsOverride = {
        'persist-fixture-data': false
    };

    const authorizeRedirectUri = 'https://authorize-ui.example/callback';

    before(function () {
        testEnvironment.pluginsToLoad = ['OAuth2'];
        testEnvironment.save();
    });

    function buildAuthorizeUrl(clientId)
    {
        const params = new URLSearchParams({
            module: 'OAuth2',
            action: 'authorize',
            response_type: 'code',
            client_id: clientId,
            redirect_uri: authorizeRedirectUri,
            scope: 'matomo:read matomo:write matomo:admin',
            state: 'ui-test-state',
        });

        return `?${params.toString()}`;
    }

    it('should show selectable scopes on the authorization page', async function () {
        const client = await testEnvironment.callApi('OAuth2.createClient', {
            name: 'Authorization UI client',
            grantTypes: ['authorization_code', 'refresh_token'],
            scope: 'matomo:admin',
            redirectUris: [authorizeRedirectUri],
            description: 'Client for authorization page UI coverage',
            type: 'confidential',
        });

        await page.goto(buildAuthorizeUrl(client.client.client_id));
        await page.waitForSelector('.card-authorize .scope-option', { visible: true });
        await page.waitForNetworkIdle();
        await page.waitForTimeout(250);

        expect(await page.screenshotSelector('.card-authorize')).to.matchImage('authorize_select_scope');
    });
});
