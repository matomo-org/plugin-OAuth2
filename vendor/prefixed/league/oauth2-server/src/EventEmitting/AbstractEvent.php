<?php

declare (strict_types=1);
namespace Matomo\Dependencies\Oauth2\League\OAuth2\Server\EventEmitting;

use Matomo\Dependencies\Oauth2\League\Event\HasEventName;
use Matomo\Dependencies\Oauth2\Psr\EventDispatcher\StoppableEventInterface;
class AbstractEvent implements StoppableEventInterface, HasEventName
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var bool
     */
    private $propagationStopped = \false;
    public function __construct(string $name)
    {
        $this->name = $name;
    }
    public function eventName() : string
    {
        return $this->name;
    }
    /**
     * Backwards compatibility method
     *
     * @deprecated use eventName instead
     */
    public function getName() : string
    {
        return $this->name;
    }
    public function isPropagationStopped() : bool
    {
        return $this->propagationStopped;
    }
    public function stopPropagation() : self
    {
        $this->propagationStopped = \true;
        return $this;
    }
}
