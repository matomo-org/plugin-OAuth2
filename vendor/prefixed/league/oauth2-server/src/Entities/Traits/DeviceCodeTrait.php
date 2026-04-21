<?php

/**
 * @author      Andrew Millington <andrew@noexceptions.io>
 * @copyright   Copyright (c) Alex Bilbie
 * @license     http://mit-license.org/
 *
 * @link        https://github.com/thephpleague/oauth2-server
 */
declare (strict_types=1);
namespace Matomo\Dependencies\Oauth2\League\OAuth2\Server\Entities\Traits;

use DateTimeImmutable;
use Matomo\Dependencies\Oauth2\League\OAuth2\Server\Entities\ClientEntityInterface;
use Matomo\Dependencies\Oauth2\League\OAuth2\Server\Entities\ScopeEntityInterface;
trait DeviceCodeTrait
{
    /**
     * @var bool
     */
    private $userApproved = \false;
    /**
     * @var bool
     */
    private $includeVerificationUriComplete = \false;
    /**
     * @var int
     */
    private $interval = 5;
    /**
     * @var string
     */
    private $userCode;
    /**
     * @var string
     */
    private $verificationUri;
    /**
     * @var \DateTimeImmutable|null
     */
    private $lastPolledAt;
    public function getUserCode() : string
    {
        return $this->userCode;
    }
    public function setUserCode(string $userCode) : void
    {
        $this->userCode = $userCode;
    }
    public function getVerificationUri() : string
    {
        return $this->verificationUri;
    }
    public function setVerificationUri(string $verificationUri) : void
    {
        $this->verificationUri = $verificationUri;
    }
    public function getVerificationUriComplete() : string
    {
        return $this->verificationUri . '?user_code=' . $this->userCode;
    }
    public abstract function getClient() : ClientEntityInterface;
    public abstract function getExpiryDateTime() : DateTimeImmutable;
    /**
     * @return ScopeEntityInterface[]
     */
    public abstract function getScopes() : array;
    /**
     * @return non-empty-string
     */
    public abstract function getIdentifier() : string;
    public function getLastPolledAt() : ?DateTimeImmutable
    {
        return $this->lastPolledAt;
    }
    public function setLastPolledAt(DateTimeImmutable $lastPolledAt) : void
    {
        $this->lastPolledAt = $lastPolledAt;
    }
    public function getInterval() : int
    {
        return $this->interval;
    }
    public function setInterval(int $interval) : void
    {
        $this->interval = $interval;
    }
    public function getUserApproved() : bool
    {
        return $this->userApproved;
    }
    public function setUserApproved(bool $userApproved) : void
    {
        $this->userApproved = $userApproved;
    }
    public function getVerificationUriCompleteInAuthResponse() : bool
    {
        return $this->includeVerificationUriComplete;
    }
    public function setVerificationUriCompleteInAuthResponse(bool $includeVerificationUriComplete) : void
    {
        $this->includeVerificationUriComplete = $includeVerificationUriComplete;
    }
}
