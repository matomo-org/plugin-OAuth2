<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2;

use League\OAuth2\Server\RedirectUriValidators\RedirectUriValidator;
use Piwik\Piwik;
use Piwik\Plugins\OAuth2\Repositories\ScopeRepository;
use Piwik\Plugins\OAuth2\Service\ClientManager;
use Piwik\Plugins\OAuth2\Model\ClientModel;

class API extends \Piwik\Plugin\API
{
    public function __construct(
        private ClientModel $clientModel,
        private ClientManager $clientManager,
        private ScopeRepository $scopeRepository
    ) {
    }

    /**
     * Lists all OAuth2 clients configured in Matomo (super users only).
     *
     * Entries include `client_id`, `name`, `description`, `redirect_uris` (array),
     * `grant_types` (array), `scopes` (array), `type` (`confidential` or `public`),
     * `active` (bool), `owner_login`, `created_at`, `updated_at`, and `last_used_at`.
     *
     * @return array[]
     */
    public function getClients(): array
    {
        Piwik::checkUserHasSuperUserAccess();
        return $this->clientModel->all();
    }

    /**
     * Returns the configured OAuth2 scopes (super users only).
     *
     * The array keys are scope identifiers (for example `matomo:read`, `matomo:write`,
     * `matomo:admin`, `offline_access`) and the values are human-readable descriptions.
     *
     * @return array<string, string>
     */
    public function getScopes(): array
    {
        Piwik::checkUserHasSuperUserAccess();
        return $this->scopeRepository->describeScopes();
    }

    /**
     * Creates a new OAuth2 client and optionally returns the generated secret.
     *
     * Super users only. For confidential clients the plaintext `secret` is returned once and must be stored securely
     * by the caller. Redirect URIs are validated and grant types must be one of `authorization_code`,
     * `client_credentials`, or `refresh_token`.
     *
     * @param string          $name          Display name shown in the Matomo UI.
     * @param string|string[] $redirectUris  Allowed redirect URIs (array or newline-separated string).
     * @param string[]        $grantTypes    Grant types to enable (`authorization_code`, `client_credentials`, `refresh_token`).
     * @param string|string[] $scopes        Scope identifiers to allow; filtered against configured scopes.
     * @param string          $description   Optional description for administrators.
     * @param string          $type          `confidential` (default, requires secret) or `public` (no client secret).
     * @param string          $active        `'1'` to enable the client or `'0'` to disable it.
     * @return array{client: array, secret: string|null}
     *
     * @throws \InvalidArgumentException When redirect URIs or grant types are invalid.
     */
    public function createClient(string $name, $redirectUris, array $grantTypes, $scopes, string $description = '', string $type = 'confidential', string $active = '1'): array
    {
        Piwik::checkUserHasSuperUserAccess();

        $type = $type === 'public' ? 'public' : 'confidential';

        $redirects = is_array($redirectUris) ? $redirectUris : preg_split('/[\r\n]+/', (string) $redirectUris);
        if ($redirects === false) {
            $redirects = [];
        }
        $redirects = array_values(array_filter(array_map('trim', $redirects), static fn($v) => $v !== ''));

        $this->validateRedirectUris($redirects);

        $grantTypes = array_values(array_filter(array_map('trim', (array) $grantTypes), static fn($v) => $v !== ''));
        $grantTypes = $this->validateGrantTypes($grantTypes);

        if ($type === 'public' && in_array('client_credentials', $grantTypes, true)) {
            throw new \InvalidArgumentException('Public clients cannot use the client_credentials grant type');
        }

        $scopes = array_values(array_intersect((array) $scopes, $this->scopeRepository->getAllowedScopeIds()));

        $result = $this->clientManager->create([
            'name' => $name,
            'description' => $description,
            'redirect_uris' => $redirects,
            'grant_types' => $grantTypes,
            'scopes' => $scopes,
            'type' => $type,
            'active' => $active,
        ], Piwik::getCurrentUserLogin());

        return $result;
    }

    // TODO: Do we require password for confirmation?
    /**
     * Generates and persists a new secret for the given OAuth2 client (super users only).
     *
     * The previous secret stops working immediately. The new plaintext secret is returned once and must be saved by the caller.
     *
     * @param string $clientId 32-character hexadecimal client identifier.
     * @return array{client_id: string, secret: string}
     *
     * @throws \InvalidArgumentException When the client ID is not a 32-character hexadecimal string.
     */
    public function rotateSecret(string $clientId): array
    {
        Piwik::checkUserHasSuperUserAccess();

        $clientId = $this->assertValidClientId($clientId);

        $secret = $this->clientManager->rotateSecret($clientId);

        return [
            'client_id' => $clientId,
            'secret' => $secret,
        ];
    }

    // TODO: Do we require password for confirmation?
    /**
     * Deletes an OAuth2 client and its related access tokens, refresh tokens, and auth codes (super users only).
     *
     * @param string $clientId 32-character hexadecimal client identifier.
     * @return array{deleted: true}
     *
     * @throws \InvalidArgumentException When the client ID is not a 32-character hexadecimal string.
     */
    public function deleteClient(string $clientId): array
    {
        Piwik::checkUserHasSuperUserAccess();

        $clientId = $this->assertValidClientId($clientId);

        $this->clientManager->delete($clientId);

        return ['deleted' => true];
    }

    private function assertValidClientId(string $clientId): string
    {
        $clientId = trim($clientId);

        if ($clientId === '' || strlen($clientId) !== 32 || !ctype_xdigit($clientId)) {
            throw new \InvalidArgumentException('clientId must be a 32 character hexadecimal string');
        }

        return $clientId;
    }

    private function validateRedirectUris(array $redirectUris): void
    {
        if (empty($redirectUris)) {
            return;
        }

        $validator = new RedirectUriValidator($redirectUris);

        foreach ($redirectUris as $redirectUri) {
            if (!$validator->validateRedirectUri($redirectUri)) {
                throw new \InvalidArgumentException('Invalid redirect_uri: ' . $redirectUri);
            }
        }
    }

    private function validateGrantTypes(array $grantTypes): array
    {
        $allowedGrantTypes = [
            'authorization_code',
            'client_credentials',
            'refresh_token',
        ];

        $invalid = array_diff($grantTypes, $allowedGrantTypes);
        if (!empty($invalid)) {
            throw new \InvalidArgumentException('Unsupported grant_types: ' . implode(', ', $invalid));
        }

        return array_values(array_unique($grantTypes));
    }
}
