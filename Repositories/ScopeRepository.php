<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Repositories;

use Matomo\Dependencies\OAuth2\League\OAuth2\Server\Entities\ClientEntityInterface;
use Matomo\Dependencies\OAuth2\League\OAuth2\Server\Entities\ScopeEntityInterface;
use Matomo\Dependencies\OAuth2\League\OAuth2\Server\Exception\OAuthServerException;
use Matomo\Dependencies\OAuth2\League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use Piwik\Piwik;
use Piwik\Plugins\OAuth2\Entities\ClientEntity;
use Piwik\Plugins\OAuth2\OAuth2;
use Piwik\Plugins\OAuth2\Entities\ScopeEntity;
use Piwik\Plugins\OAuth2\SystemSettings;

class ScopeRepository implements ScopeRepositoryInterface
{
    private SystemSettings $settings;

    public function __construct(SystemSettings $settings)
    {
        $this->settings = $settings;
    }

    public static function getScopeDescriptions(): array
    {
        return OAuth2::getScopeDescriptions();
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
        if (count($scopes) > 1) {
            throw OAuthServerException::invalidScope(Piwik::translate('OAuth2_MultipleScopesNotAllowed'));
        }

        $allowed = $this->getAllowedScopeIds();
        if ($clientEntity instanceof ClientEntity && !empty($clientEntity->getAllowedScopes())) {
            $clientScopes = $clientEntity->getAllowedScopes();

            // A user can consent to less than the client is configured for, so codes and the
            // refresh tokens that follow them may carry a lower scope. Client credentials have no
            // user to make that choice, so those clients keep getting exactly what is configured.
            if (in_array($grantType, ['authorization_code', 'refresh_token'], true)) {
                $clientScopes = self::expandScopes($clientScopes);
            }

            $allowed = array_values(array_intersect($allowed, $clientScopes));
        }

        if (empty($scopes)) {
            $defaultScope = 'matomo:read';
            if (!in_array($defaultScope, $allowed, true)) {
                throw OAuthServerException::invalidScope($defaultScope);
            }
            $scopes = [$this->getScopeEntityByIdentifier($defaultScope)];
        }

        $final = [];
        foreach ($scopes as $scope) {
            $identifier = $scope instanceof ScopeEntityInterface ? $scope->getIdentifier() : null;
            if ($identifier === null || !in_array($identifier, $allowed, true)) {
                throw OAuthServerException::invalidScope($identifier ?? '');
            }

            $final[] = $scope;
        }

        return $final;
    }

    /**
     * Expands configured client scopes to every scope they permit, as a client scope is the
     * maximum access level its tokens may get rather than the only one it can be granted.
     *
     * @param string[] $scopes
     * @return string[]
     */
    public static function expandScopes(array $scopes): array
    {
        $expanded = [];

        foreach ($scopes as $scope) {
            $expanded = array_merge($expanded, OAuth2::expandScopeHierarchically($scope));
        }

        return array_values(array_unique($expanded));
    }

    public function getAllowedScopeIds(): array
    {
        $configured = $this->settings->defaultScopes->getValue();
        $configured = is_array($configured) ? $configured : [];

        return array_values(array_intersect(array_keys(self::getScopeDescriptions()), $configured));
    }

    public function describeScopes(): array
    {
        $allowed = $this->getAllowedScopeIds();
        $result = [];
        foreach ($allowed as $identifier) {
            $result[$identifier] = self::getScopeDescriptions()[$identifier] ?? $identifier;
        }

        return $result;
    }
}
