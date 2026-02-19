<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Repositories;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use Piwik\Plugins\OAuth2\Entities\ClientEntity;
use Piwik\Plugins\OAuth2\Model\ClientModel;

class ClientRepository implements ClientRepositoryInterface
{
    private ClientModel $model;

    public function __construct(ClientModel $model)
    {
        $this->model = $model;
    }

    public function getClientEntity(string $clientIdentifier): ?ClientEntityInterface
    {
        $row = $this->model->find($clientIdentifier);
        if (empty($row) || !$row['active']) {
            return null;
        }

        return $this->mapRowToEntity($row);
    }

    public function validateClient(string $clientIdentifier, ?string $clientSecret, ?string $grantType): bool
    {
        $row = $this->model->find($clientIdentifier);
        if (empty($row) || !$row['active']) {
            return false;
        }

        if (!empty($grantType) && !empty($row['grant_types']) && !in_array($grantType, $row['grant_types'], true)) {
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
