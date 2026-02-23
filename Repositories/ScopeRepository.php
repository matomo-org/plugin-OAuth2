<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Repositories;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use Piwik\Access;
use Piwik\Plugins\OAuth2\Entities\ScopeEntity;
use Piwik\Plugins\OAuth2\SystemSettings;

class ScopeRepository implements ScopeRepositoryInterface
{
    private const DESCRIPTIONS = [
        'matomo:read' => 'Read analytics data you can access.',
        'matomo:write' => 'Create and modify analytics configuration.',
        'matomo:superuser' => 'Matomo superuser-level operations.',
        'offline_access' => 'Access Matomo when you’re not actively using it.',
    ];

    private SystemSettings $settings;

    public function __construct(SystemSettings $settings)
    {
        $this->settings = $settings;
    }

    public function getScopeEntityByIdentifier(string $identifier): ?ScopeEntityInterface
    {
        if (!in_array($identifier, $this->getAllowedScopeIds(), true)) {
            return null;
        }

        $scope = new ScopeEntity();
        $scope->setIdentifier($identifier);

        return $scope;
    }

    public function finalizeScopes(
        array $scopes,
        string $grantType,
        ClientEntityInterface $clientEntity,
        string|null $userIdentifier = null,
        ?string $authCodeId = null
    ): array {
        $allowed = $this->getAllowedScopeIds();
        if (!empty($clientEntity->allowedScopes)) {
            $allowed = array_values(array_intersect($allowed, $clientEntity->allowedScopes));
        }

        if (empty($scopes)) {
            $defaultScope = 'matomo:read';
            if (!in_array($defaultScope, $allowed, true)) {
                return [];
            }
            $scopes = [$this->getScopeEntityByIdentifier($defaultScope)];
        }

        $final = [];
        foreach ($scopes as $scope) {
            $identifier = $scope instanceof ScopeEntityInterface ? $scope->getIdentifier() : null;
            if ($identifier === null || !in_array($identifier, $allowed, true)) {
                throw OAuthServerException::invalidScope($identifier ?? '');
            }

            if ($identifier === 'offline_access' && !$this->settings->enableRefreshTokens->getValue()) {
                throw OAuthServerException::invalidScope($identifier);
            }

            if ($identifier === 'matomo:superuser' && !Access::getInstance()->hasSuperUserAccess()) {
                throw OAuthServerException::invalidScope($identifier);
            }

            $final[] = $scope;
        }

        return $final;
    }

    public function getAllowedScopeIds(): array
    {
        $configured = $this->settings->defaultScopes->getValue();
        $configured = is_array($configured) ? $configured : [];

        return array_values(array_intersect(array_keys(self::DESCRIPTIONS), $configured));
    }

    public function describeScopes(): array
    {
        $allowed = $this->getAllowedScopeIds();
        $result = [];
        foreach ($allowed as $identifier) {
            $result[$identifier] = self::DESCRIPTIONS[$identifier] ?? $identifier;
        }

        return $result;
    }
}
