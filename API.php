<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Oauth2;

use League\OAuth2\Server\RedirectUriValidators\RedirectUriValidator;
use Piwik\Piwik;
use Piwik\Plugins\Oauth2\Repositories\ScopeRepository;
use Piwik\Plugins\Oauth2\Service\ClientManager;
use Piwik\Plugins\Oauth2\Model\ClientModel;

class API extends \Piwik\Plugin\API
{
    public function __construct(
        private ClientModel $clientModel,
        private ClientManager $clientManager,
        private ScopeRepository $scopeRepository
    ) {
    }

    public function getClients(): array
    {
        Piwik::checkUserHasSuperUserAccess();
        return $this->clientModel->all();
    }

    public function getScopes(): array
    {
        Piwik::checkUserHasSuperUserAccess();
        return $this->scopeRepository->describeScopes();
    }

    public function createClient(string $name, $redirectUris, array $grantTypes, $scopes, string $description = '', string $type = 'confidential', string $active = '1'): array
    {
        Piwik::checkUserHasSuperUserAccess();

        $redirects = is_array($redirectUris) ? $redirectUris : preg_split('/[\r\n]+/', (string) $redirectUris);
        if ($redirects === false) {
            $redirects = [];
        }
        $redirects = array_values(array_filter(array_map('trim', $redirects), static fn($v) => $v !== ''));

        $this->validateRedirectUris($redirects);

        $grantTypes = array_values(array_filter(array_map('trim', (array) $grantTypes), static fn($v) => $v !== ''));
        $grantTypes = $this->validateGrantTypes($grantTypes);

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
