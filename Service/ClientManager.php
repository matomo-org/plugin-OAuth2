<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Oauth2\Service;

use Piwik\Plugins\Oauth2\Model\AccessTokenModel;
use Piwik\Plugins\Oauth2\Model\AuthCodeModel;
use Piwik\Plugins\Oauth2\Model\ClientModel;
use Piwik\Plugins\Oauth2\Model\RefreshTokenModel;

class ClientManager
{
    public function __construct(
        private ClientModel $clientModel,
        private AccessTokenModel $accessTokenModel,
        private RefreshTokenModel $refreshTokenModel,
        private AuthCodeModel $authCodeModel
    ) {
    }

    public function create(array $data, string $ownerLogin): array
    {
        $clientId = $data['client_id'] ?? bin2hex(random_bytes(16));
        $secret = $this->shouldUseSecret($data) ? $this->generateSecret() : null;
        $secretHash = $secret ? password_hash($secret, PASSWORD_DEFAULT) : null;

        $type = $this->normalizeType($data['type'] ?? null);
        $grantTypes = $this->normalizeGrantTypes($data['grant_types'] ?? []);
        $this->assertGrantTypesAreAllowed($grantTypes, $type);

        $client = $this->clientModel->create([
            'client_id' => $clientId,
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'secret_hash' => $secretHash,
            'redirect_uris' => $data['redirect_uris'] ?? [],
            'grant_types' => $grantTypes,
            'scopes' => $data['scopes'] ?? [],
            'type' => $type,
            'active' => $data['active'] ?? true,
            'owner_login' => $ownerLogin,
        ]);

        return ['client' => $client, 'secret' => $secret];
    }

    public function update(string $clientId, array $data, ?string $ownerLogin = null): void
    {
        $type = $this->normalizeType($data['type'] ?? null);
        $grantTypes = $this->normalizeGrantTypes($data['grant_types'] ?? []);
        $this->assertGrantTypesAreAllowed($grantTypes, $type);

        $this->clientModel->update($clientId, [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'redirect_uris' => $data['redirect_uris'] ?? [],
            'grant_types' => $grantTypes,
            'scopes' => $data['scopes'] ?? [],
            'type' => $type,
            'active' => $data['active'] ?? true,
            'owner_login' => $ownerLogin ?? $data['owner_login'],
        ]);
    }

    public function rotateSecret(string $clientId): ?string
    {
        $secret = $this->generateSecret();
        $this->clientModel->rotateSecret($clientId, password_hash($secret, PASSWORD_DEFAULT));

        return $secret;
    }

    public function delete(string $clientId): void
    {
        $this->refreshTokenModel->deleteByClient($clientId);
        $this->accessTokenModel->deleteByClient($clientId);
        $this->authCodeModel->deleteByClient($clientId);
        $this->clientModel->delete($clientId);
    }

    private function generateSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function shouldUseSecret(array $data): bool
    {
        return ($data['type'] ?? 'confidential') !== 'public';
    }

    private function normalizeType(?string $type): string
    {
        return $type === 'public' ? 'public' : 'confidential';
    }

    private function normalizeGrantTypes(array $grantTypes): array
    {
        $normalized = array_values(array_unique(array_map('trim', $grantTypes)));
        $normalized = array_filter($normalized, static function ($value) {
            return $value !== '';
        });

        return $normalized;
    }

    private function assertGrantTypesAreAllowed(array $grantTypes, string $type): void
    {
        if ($type === 'public' && in_array('client_credentials', $grantTypes, true)) {
            throw new \InvalidArgumentException('Public clients cannot use the client_credentials grant type');
        }
    }
}
