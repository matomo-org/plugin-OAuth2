<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Service;

use Piwik\Date;
use Piwik\Piwik;
use Piwik\Plugins\OAuth2\Model\AccessTokenModel;
use Piwik\Plugins\OAuth2\Model\AuthCodeModel;
use Piwik\Plugins\OAuth2\Model\ClientModel;
use Piwik\Plugins\OAuth2\Model\RefreshTokenModel;
use Piwik\Plugins\UsersManager\Model as UserModel;

class ClientManager
{
    public function __construct(
        private ClientModel $clientModel,
        private AccessTokenModel $accessTokenModel,
        private RefreshTokenModel $refreshTokenModel,
        private AuthCodeModel $authCodeModel,
        private UserModel $userModel
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

        if ((bool) ($data['active'] ?? true)) {
            $this->assertOwnerExists($existingClient);
        }

        $secret = null;
        $secretHash = $existingClient['secret_hash'] ?? null;
        $existingType = $this->normalizeType($existingClient['type'] ?? null);
        $isDowngradingToPublic = $existingType === 'confidential' && $type === 'public';

        if ($existingType === 'public' && $type === 'confidential') {
            $secret = $this->generateSecret();
            $secretHash = password_hash($secret, PASSWORD_DEFAULT);
        } elseif ($type === 'public') {
            $secretHash = null;
        }

        if ($isDowngradingToPublic) {
            $this->revokeIssuedCredentials($clientId);
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

        if ($active) {
            $this->assertOwnerExists($existingClient);
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

    /**
     * Removes every credential issued to the given user, including the ones issued for clients
     * owned by somebody else. The clients themselves are left untouched, only their owner may
     * remove them.
     */
    public function deleteCredentialsForUser(string $userLogin): void
    {
        $this->refreshTokenModel->deleteByUserLogin($userLogin);
        $this->accessTokenModel->deleteByUserLogin($userLogin);
        $this->authCodeModel->deleteByUserLogin($userLogin);
    }

    /**
     * Pauses every client the given user owns and removes the credentials issued for them.
     *
     * The owner is cleared rather than kept, as tokens of a client act as its owner, and the login
     * is free to be given to somebody else. A client paused this way can therefore never be resumed.
     *
     * @return array The clients that were paused, as they were stored before.
     */
    public function pauseClientsForOwner(string $ownerLogin): array
    {
        return $this->pauseAndClearOwner($this->clientModel->allByOwner($ownerLogin));
    }

    /**
     * Pauses the clients whose owner no longer exists, such as those of users deleted before
     * {@see pauseClientsForOwner()} was called on user deletion.
     *
     * @return array The clients that were paused, as they were stored before.
     */
    public function pauseClientsWithoutOwner(): array
    {
        $clients = array_filter($this->clientModel->all(), function (array $client): bool {
            return ($client['owner_login'] ?? '') !== '' && !$this->ownerExists($client);
        });

        return $this->pauseAndClearOwner(array_values($clients));
    }

    private function pauseAndClearOwner(array $clients): array
    {
        foreach ($clients as $client) {
            // credentials first, so failing part way through never leaves a usable token behind
            $this->refreshTokenModel->deleteByClient($client['client_id']);
            $this->accessTokenModel->deleteByClient($client['client_id']);
            $this->authCodeModel->deleteByClient($client['client_id']);
            $this->clientModel->pauseAndClearOwner($client['client_id']);
        }

        return $clients;
    }

    private function ownerExists(array $client): bool
    {
        if (empty($client['owner_login'])) {
            return false;
        }

        $owner = $this->userModel->getUser($client['owner_login']);

        return !empty($owner['login']);
    }

    private function assertOwnerExists(array $client): void
    {
        if ($this->ownerExists($client)) {
            return;
        }

        // an owner is still set when the client was never paused, eg after a failed cleanup
        throw new \InvalidArgumentException(Piwik::translate(empty($client['owner_login'])
            ? 'OAuth2_ClientOwnerDeletedCannotActivate'
            : 'OAuth2_ClientOwnerMissingCannotActivate'));
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
        if (empty($grantTypes)) {
            throw new \InvalidArgumentException('At least one grant type is required');
        }

        if ($type === 'public' && in_array('client_credentials', $grantTypes, true)) {
            throw new \InvalidArgumentException('Public clients cannot use the client_credentials grant type');
        }
    }

    private function revokeIssuedCredentials(string $clientId): void
    {
        $this->refreshTokenModel->revokeByClient($clientId);
        $this->accessTokenModel->revokeByClient($clientId);
        $this->authCodeModel->revokeByClient($clientId);
    }
}
