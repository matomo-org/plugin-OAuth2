<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Entities;

use Matomo\Dependencies\Oauth2\League\OAuth2\Server\Entities\ClientEntityInterface;
use Matomo\Dependencies\Oauth2\League\OAuth2\Server\Entities\Traits\EntityTrait;

class ClientEntity implements ClientEntityInterface
{
    use EntityTrait;

    /**
     * @var string[]
     */
    private array $redirectUris = [];

    private string $name = '';

    private bool $confidential = true;

    /**
     * @var string[]
     */
    public array $allowedGrantTypes = [];

    /**
     * @var string[]
     */
    public array $allowedScopes = [];

    public bool $active = true;

    public string $type = 'confidential';

    public ?string $ownerLogin = null;

    /**
     * @return string[]
     */
    public function getAllowedScopes(): array
    {
        return $this->allowedScopes;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getRedirectUri(): string|array
    {
        if (count($this->redirectUris) === 1) {
            return $this->redirectUris[0];
        }

        return $this->redirectUris;
    }

    public function setRedirectUris(array $redirectUris): void
    {
        $this->redirectUris = $redirectUris;
    }

    public function isConfidential(): bool
    {
        return $this->confidential;
    }

    public function setConfidential(bool $confidential): void
    {
        $this->confidential = $confidential;
        $this->type = $confidential ? 'confidential' : 'public';
    }
}
