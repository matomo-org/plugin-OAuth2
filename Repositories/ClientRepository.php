<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Repositories;

use Matomo\Dependencies\OAuth2\League\OAuth2\Server\Entities\ClientEntityInterface;
use Matomo\Dependencies\OAuth2\League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use Piwik\Plugins\OAuth2\Entities\ClientEntity;
use Piwik\Plugins\OAuth2\Model\ClientModel;
use Piwik\Plugins\UsersManager\Model as UserModel;

class ClientRepository implements ClientRepositoryInterface
{
    private ClientModel $model;
    private UserModel $userModel;

    public function __construct(ClientModel $model, UserModel $userModel)
    {
        $this->model = $model;
        $this->userModel = $userModel;
    }

    public function getClientEntity(string $clientIdentifier): ?ClientEntityInterface
    {
        $row = $this->model->find($clientIdentifier);
        if (empty($row) || !$row['active'] || !$this->hasExistingOwner($row)) {
            return null;
        }

        return $this->mapRowToEntity($row);
    }

    public function validateClient(string $clientIdentifier, ?string $clientSecret, ?string $grantType): bool
    {
        $row = $this->model->find($clientIdentifier);
        if (empty($row) || !$row['active'] || !$this->hasExistingOwner($row)) {
            return false;
        }

        if (!empty($grantType) && !empty($row['grant_types']) && !in_array($grantType, $row['grant_types'], true)) {
            return false;
        }

        if ($grantType === 'client_credentials' && ($row['type'] ?? 'confidential') === 'public') {
            return false;
        }

        if ($row['type'] === 'public') {
            return true;
        }

        if (empty($row['secret_hash']) || $clientSecret === null) {
            return false;
        }

        return password_verify($clientSecret, $row['secret_hash']);
    }

    /**
     * A client whose owner no longer exists must not take part in any grant, as its tokens would
     * be issued for a login that is free to be given to somebody else. Clients are removed when
     * their owner is deleted, so this only ever holds for the ones a failed cleanup left behind.
     */
    private function hasExistingOwner(array $row): bool
    {
        if (empty($row['owner_login'])) {
            return false;
        }

        $owner = $this->userModel->getUser($row['owner_login']);

        return !empty($owner['login']);
    }

    private function mapRowToEntity(array $row): ClientEntity
    {
        $entity = new ClientEntity();
        $entity->setIdentifier($row['client_id']);
        $entity->setName($row['name']);
        $entity->setRedirectUris($row['redirect_uris']);
        $entity->setConfidential($row['type'] !== 'public');
        $entity->allowedGrantTypes = $row['grant_types'] ?? [];
        $entity->allowedScopes = $row['scopes'] ?? [];
        $entity->active = (bool) $row['active'];
        $entity->ownerLogin = $row['owner_login'] ?? null;

        return $entity;
    }
}
