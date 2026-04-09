<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Service;

use Piwik\Date;
use Piwik\Plugins\OAuth2\Model\AccessTokenModel;
use Piwik\Plugins\OAuth2\Model\AuthCodeModel;
use Piwik\Plugins\OAuth2\Model\ClientModel;
use Piwik\Plugins\OAuth2\Model\RefreshTokenModel;

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
        $now = Date::now()->getDatetime();

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
            'created_at' => $now,
            'updated_at' => $now,
            'owner_login' => $ownerLogin,
        ]);

        return ['client' => $client, 'secret' => $secret];
    }

    public function update(string $clientId, array $data): array
    {
        $existingClient = $this->clientModel->find($clientId);
        if (empty($existingClient)) {
            throw new \InvalidArgumentException('Client not found');
        }

        $type = $this->normalizeType($data['type'] ?? null);
        $grantTypes = $this->normalizeGrantTypes($data['grant_types'] ?? []);
        $this->assertGrantTypesAreAllowed($grantTypes, $type);

        $secret = null;
        $secretHash = $existingClient['secret_hash'] ?? null;
        $existingType = $this->normalizeType($existingClient['type'] ?? null);

        if ($existingType === 'public' && $type === 'confidential') {
            $secret = $this->generateSecret();
            $secretHash = password_hash($secret, PASSWORD_DEFAULT);
        } elseif ($type === 'public') {
            $secretHash = null;
        }

        $this->clientModel->update($clientId, [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'redirect_uris' => $data['redirect_uris'] ?? [],
            'grant_types' => $grantTypes,
            'scopes' => $data['scopes'] ?? [],
            'secret_hash' => $secretHash,
            'type' => $type,
            'active' => $data['active'] ?? true,
            'owner_login' => $data['owner_login'] ?? ($existingClient['owner_login'] ?? null),
        ]);

        $client = $this->clientModel->find($clientId);

        return ['client' => $client, 'secret' => $secret];
    }

    public function rotateSecret(string $clientId): ?string
    {
        $secret = $this->generateSecret();
        $this->clientModel->rotateSecret($clientId, password_hash($secret, PASSWORD_DEFAULT));

        return $secret;
    }

    public function setActive(string $clientId, bool $active): array
    {
        $existingClient = $this->clientModel->find($clientId);
        if (empty($existingClient)) {
            throw new \InvalidArgumentException('Client not found');
        }

        $this->clientModel->setActive($clientId, $active);

        return $this->clientModel->find($clientId);
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
