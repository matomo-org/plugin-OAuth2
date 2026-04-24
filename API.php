<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2;

use Matomo\Dependencies\OAuth2\League\OAuth2\Server\RedirectUriValidators\RedirectUriValidator;
use Piwik\Piwik;
use Piwik\Plugins\OAuth2\Repositories\ScopeRepository;
use Piwik\Plugins\OAuth2\Service\ClientManager;
use Piwik\Plugins\OAuth2\Model\ClientModel;
use Piwik\UrlHelper;

/**
 * Exposes super-user OAuth2 client management endpoints for Matomo.
 *
 * This API lists configured scopes and lets administrators create, inspect, update, rotate, activate, and delete OAuth2 clients.
 */
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
     * @return array<int, array<string, mixed>> OAuth2 clients ordered by most recently updated first.
     */
    public function getClients(): array
    {
        Piwik::checkUserHasSuperUserAccess();
        return array_map([$this, 'sanitizeClient'], $this->clientModel->all());
    }

    /**
     * Returns one OAuth2 client configured in Matomo (super users only).
     *
     * @param string $clientId 32-character hexadecimal client identifier.
     * @return array<string, mixed> Sanitized OAuth2 client details for the requested client.
     */
    public function getClient(string $clientId): array
    {
        Piwik::checkUserHasSuperUserAccess();

        $clientId = $this->assertValidClientId($clientId);
        $client = $this->clientModel->find($clientId);

        if (empty($client)) {
            throw new \InvalidArgumentException(Piwik::translate('OAuth2_ClientNotFound'));
        }

        return $this->sanitizeClient($client);
    }

    /**
     * Returns the OAuth2 scopes enabled for client configuration (super users only).
     *
     * The array keys are scope identifiers (for example `matomo:read`, `matomo:write`,
     * `matomo:admin`, `matomo:superuser`) and the values are human-readable descriptions.
     *
     * @return array<string, string> Enabled scope identifiers mapped to their translated descriptions.
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
     * @param string[]        $grantTypes    Grant types to enable (`authorization_code`, `client_credentials`, `refresh_token`).
     * @param string          $scope         Single scope identifier to allow for the client. Must match one of the enabled OAuth2 scopes.
     * @param string|string[] $redirectUris Allowed redirect URIs (array or newline-separated string).
     * @param string          $description   Optional description for administrators.
     * @param string          $type          `confidential` (default, requires secret) or `public` (no client secret).
     * @param string          $active        `'1'` to enable the client or `'0'` to disable it.
     * @return array{client: array<string, mixed>, secret: string|null} The sanitized client record and the generated plaintext
     *                                                                  secret when the created client is confidential.
     */
    public function createClient(string $name, array $grantTypes, string $scope, $redirectUris = [], string $description = '', string $type = 'confidential', string $active = '1'): array
    {
        Piwik::checkUserHasSuperUserAccess();

        $data = $this->buildValidatedClientData(
            $name,
            $grantTypes,
            $scope,
            $redirectUris,
            $description,
            $type,
            $active
        );

        $result = $this->clientManager->create([
            'name' => $data['name'],
            'description' => $data['description'],
            'redirect_uris' => $data['redirect_uris'],
            'grant_types' => $data['grant_types'],
            'scopes' => $data['scopes'],
            'type' => $data['type'],
            'active' => $data['active'],
        ], Piwik::getCurrentUserLogin());

        $result['client'] = $this->sanitizeClient($result['client']);

        return $result;
    }

    /**
     * Updates an OAuth2 client and optionally returns a newly generated secret.
     *
     * If a public client is converted to a confidential client, this endpoint returns the new plaintext `secret` once.
     *
     * @param string          $clientId     32-character hexadecimal client identifier.
     * @param string          $name         Display name shown in the Matomo UI.
     * @param string[]        $grantTypes   Grant types to enable (`authorization_code`, `client_credentials`, `refresh_token`).
     * @param string          $scope        Single scope identifier to allow for the client. Must match one of the enabled OAuth2 scopes.
     * @param string|string[] $redirectUris Allowed redirect URIs (array or newline-separated string).
     * @param string          $description  Optional description for administrators.
     * @param string          $type         `confidential` (default, requires secret) or `public` (no client secret).
     * @param string          $active       `'1'` to enable the client or `'0'` to disable it.
     * @return array{client: array<string, mixed>, secret: string|null} The sanitized updated client record and a newly generated
     *                                                                  plaintext secret when the client becomes confidential.
     */
    public function updateClient(string $clientId, string $name, array $grantTypes, string $scope, $redirectUris = [], string $description = '', string $type = 'confidential', string $active = '1'): array
    {
        Piwik::checkUserHasSuperUserAccess();

        $clientId = $this->assertValidClientId($clientId);

        if (empty($this->clientModel->find($clientId))) {
            throw new \InvalidArgumentException(Piwik::translate('OAuth2_ClientNotFound'));
        }

        $data = $this->buildValidatedClientData(
            $name,
            $grantTypes,
            $scope,
            $redirectUris,
            $description,
            $type,
            $active
        );

        $result = $this->clientManager->update($clientId, [
            'name' => $data['name'],
            'description' => $data['description'],
            'redirect_uris' => $data['redirect_uris'],
            'grant_types' => $data['grant_types'],
            'scopes' => $data['scopes'],
            'type' => $data['type'],
            'active' => $data['active'],
        ]);

        $result['client'] = $this->sanitizeClient($result['client']);

        return $result;
    }

    // TODO: Do we require password for confirmation?
    /**
     * Generates and persists a new secret for the given OAuth2 client (super users only).
     *
     * The previous secret stops working immediately. The new plaintext secret is returned once and must be saved by the caller.
     *
     * @param string $clientId 32-character hexadecimal client identifier.
     * @return array{client_id: string, secret: string} The client ID and the newly generated plaintext secret.
     */
    public function rotateSecret(string $clientId): array
    {
        Piwik::checkUserHasSuperUserAccess();

        $clientId = $this->assertValidClientId($clientId);
        $client = $this->getClient($clientId);
        if (empty($clientId) || empty($client['type']) || $client['type'] != 'confidential') {
            throw new \InvalidArgumentException(Piwik::translate('OAuth2_InvalidClientToRotateSecretExceptionMessage'));
        }

        $secret = $this->clientManager->rotateSecret($clientId);

        return [
            'client_id' => $clientId,
            'secret' => $secret,
        ];
    }

    /**
     * Updates whether an OAuth2 client is active (super users only).
     *
     * @param string $clientId 32-character hexadecimal client identifier.
     * @param string $active `'1'` to enable the client or `'0'` to disable it.
     * @return array{client: array<string, mixed>} The sanitized client record after the active flag is updated.
     */
    public function setClientActive(string $clientId, string $active): array
    {
        Piwik::checkUserHasSuperUserAccess();

        $clientId = $this->assertValidClientId($clientId);
        $client = $this->clientManager->setActive($clientId, $active === '1');

        return [
            'client' => $this->sanitizeClient($client),
        ];
    }

    // TODO: Do we require password for confirmation?
    /**
     * Deletes an OAuth2 client and its related access tokens, refresh tokens, and auth codes (super users only).
     *
     * @param string $clientId 32-character hexadecimal client identifier.
     * @return array{deleted: true} Confirmation that the client and its related OAuth2 records were deleted.
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
            throw new \InvalidArgumentException(Piwik::translate('OAuth2_ClientIdException'));
        }

        return $clientId;
    }

    private function buildValidatedClientData(
        string $name,
        array $grantTypes,
        string $scope,
        $redirectUris,
        string $description,
        string $type,
        string $active
    ): array {
        $type = $type === 'public' ? 'public' : 'confidential';

        $redirects = is_array($redirectUris) ? $redirectUris : preg_split('/[\r\n]+/', (string) $redirectUris);
        if ($redirects === false) {
            $redirects = [];
        }
        $redirects = array_values(array_filter(array_map('trim', $redirects), static function ($value) {
            return $value !== '';
        }));

        $grantTypes = array_values(array_filter(array_map('trim', (array) $grantTypes), static function ($value) {
            return $value !== '';
        }));
        $grantTypes = $this->validateGrantTypes($grantTypes);

        $this->validateRedirectUris($redirects, $grantTypes);

        if ($type === 'public' && in_array('client_credentials', $grantTypes, true)) {
            throw new \InvalidArgumentException(Piwik::translate('OAuth2_ClientCredentialsExceptionPublicClient'));
        }

        $scope = array_values(array_intersect([$scope], $this->scopeRepository->getAllowedScopeIds()));

        if (empty($scope)) {
            throw new \InvalidArgumentException(Piwik::translate('OAuth2_InvalidScopeValue'));
        }

        return [
            'name' => trim($name),
            'description' => $description,
            'redirect_uris' => $redirects,
            'grant_types' => $grantTypes,
            'scopes' => $scope,
            'type' => $type,
            'active' => $active,
        ];
    }

    private function validateRedirectUris(array $redirectUris, array $grantTypes): void
    {
        if (!in_array('authorization_code', $grantTypes, true)) {
            return;
        }

        if (empty($redirectUris)) {
            throw new \InvalidArgumentException(Piwik::translate('OAuth2_InvalidRedirectUri'));
        }

        $validator = new RedirectUriValidator($redirectUris);

        foreach ($redirectUris as $redirectUri) {
            if (!$validator->validateRedirectUri($redirectUri) || !UrlHelper::isLookLikeUrl($redirectUri)) {
                throw new \InvalidArgumentException(Piwik::translate('OAuth2_InvalidRedirectUri') . ': ' . $redirectUri);
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
            throw new \InvalidArgumentException(Piwik::translate('OAuth2_InvalidGrantTypes') . ': ' . implode(', ', $invalid));
        }

        return array_values(array_unique($grantTypes));
    }

    private function sanitizeClient(array $client): array
    {
        unset($client['secret_hash']);

        return $client;
    }
}
