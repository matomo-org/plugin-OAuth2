<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Service;

use Piwik\Piwik;
use Piwik\Plugins\OAuth2\Repositories\ScopeRepository;
use Piwik\Plugins\OAuth2\SystemSettings;
use Piwik\Url;

/**
 * Builds the OAuth 2.0 Authorization Server Metadata document served at
 * /.well-known/oauth-authorization-server as defined by RFC 8414.
 *
 * @link https://datatracker.ietf.org/doc/html/rfc8414
 */
class AuthorizationServerMetadata
{
    /**
     * Discovery path (RFC 8414). The web server is expected to forward this path to the
     * metadata controller action; see the plugin README for the rewrite rules.
     */
    public const WELL_KNOWN_PATH = '/.well-known/oauth-authorization-server';

    public function __construct(
        private SystemSettings $settings,
        private ScopeRepository $scopeRepository
    ) {
    }

    /**
     * @return array<string, mixed> JSON-serializable metadata document.
     */
    public function build(): array
    {
        // The issuer is the current request URL with the well-known segment removed.
        // RFC 8414 (section 3.1) inserts that segment between the host and the issuer's path
        // component (e.g. https://host/.well-known/oauth-authorization-server/sub => issuer
        // https://host/sub), while the appended form (https://host/sub/.well-known/...) is also
        // seen in practice. Removing the segment wherever it appears yields the issuer for both,
        // and resolves the host and any subdirectory automatically.
        $currentUrl = Url::getCurrentUrlWithoutQueryString();
        $issuer = rtrim(str_replace(self::WELL_KNOWN_PATH, '', $currentUrl), '/');
        $baseUrl = $issuer . '/';

        $authorizationCodeEnabled = (bool) $this->settings->enableAuthorizationCode->getValue();
        $clientCredentialsEnabled = (bool) $this->settings->enableClientCredentials->getValue();
        $refreshTokensEnabled = (bool) $this->settings->enableRefreshTokens->getValue();

        $grantTypes = [];
        if ($authorizationCodeEnabled) {
            $grantTypes[] = 'authorization_code';
        }
        if ($clientCredentialsEnabled) {
            $grantTypes[] = 'client_credentials';
        }
        if ($refreshTokensEnabled) {
            $grantTypes[] = 'refresh_token';
        }

        $metadata = ['issuer' => $issuer];

        if ($authorizationCodeEnabled) {
            $metadata['authorization_endpoint'] = $baseUrl . 'index.php?module=OAuth2&action=authorize';
        }

        $metadata['token_endpoint'] = $baseUrl . 'index.php?module=OAuth2&action=token';
        $metadata['scopes_supported'] = $this->scopeRepository->getAllowedScopeIds();

        if ($authorizationCodeEnabled) {
            $metadata['response_types_supported'] = ['code'];
            $metadata['code_challenge_methods_supported'] = ['S256', 'plain'];
        }

        $metadata['grant_types_supported'] = $grantTypes;
        $metadata['token_endpoint_auth_methods_supported'] = ['client_secret_basic', 'client_secret_post'];

        /**
         * Triggered after the OAuth 2.0 Authorization Server Metadata document (RFC 8414) is built,
         * before it is served at `/.well-known/oauth-authorization-server`.
         *
         * Other plugins (e.g. an MCP or AI integration) can use this to add or override fields,
         * such as `registration_endpoint`, `introspection_endpoint`, `revocation_endpoint`,
         * `jwks_uri`, or any custom discovery metadata.
         *
         * @param array &$metadata The JSON-serializable metadata document.
         */
        Piwik::postEvent('OAuth2.authorizationServerMetadata', [&$metadata]);

        return $metadata;
    }
}
