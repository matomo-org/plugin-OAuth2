<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Repositories;

use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use Piwik\Plugins\OAuth2\Entities\AuthCodeEntity;
use Piwik\Plugins\OAuth2\Model\AuthCodeModel;

class AuthCodeRepository implements AuthCodeRepositoryInterface
{
    private AuthCodeModel $model;

    public function __construct(AuthCodeModel $model)
    {
        $this->model = $model;
    }

    public function getNewAuthCode(): AuthCodeEntityInterface
    {
        $code = new AuthCodeEntity();
        $code->setIdentifier($this->generateIdentifier());

        return $code;
    }

    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void
    {
        $this->model->persist([
            'code_id' => $authCodeEntity->getIdentifier(),
            'user_login' => $authCodeEntity->getUserIdentifier(),
            'client_id' => $authCodeEntity->getClient()->getIdentifier(),
            'scopes' => array_map(static function (ScopeEntityInterface $scope) {
                return $scope->getIdentifier();
            }, $authCodeEntity->getScopes()),
            'redirect_uri' => $authCodeEntity->getRedirectUri(),
            'code_challenge' => method_exists($authCodeEntity, 'getCodeChallenge') ? $authCodeEntity->getCodeChallenge() : null,
            'code_challenge_method' => method_exists($authCodeEntity, 'getCodeChallengeMethod') ? $authCodeEntity->getCodeChallengeMethod() : null,
            'expires_at' => $authCodeEntity->getExpiryDateTime()->format('Y-m-d H:i:s'),
        ]);
    }

    public function revokeAuthCode(string $codeId): void
    {
        $this->model->revoke($codeId);
    }

    public function isAuthCodeRevoked(string $codeId): bool
    {
        return $this->model->isRevoked($codeId);
    }

    private function generateIdentifier(): string
    {
        return bin2hex(random_bytes(16));
    }
}
