<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Security;

use NetBull\MediaBundle\Entity\MediaInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SessionSecurityStrategy implements SecurityStrategyInterface
{
    protected RequestStack $requestStack;

    protected int $times;

    protected string $sessionKey = 'SomethingReallyCool';

    public function __construct(RequestStack $requestStack, int $times)
    {
        $this->times = $times;
        $this->requestStack = $requestStack;
    }

    public function isGranted(MediaInterface $media, Request $request): bool
    {
        if (!$session = $this->requestStack->getSession()) {
            return false;
        }

        if (!$session->has('session')) {
            return false;
        }

        $times = $session->get($this->sessionKey, 0);

        if ($times >= $this->times) {
            return false;
        }

        ++$times;

        $session->set($this->sessionKey, $times);

        return true;
    }

    public function getDescription(): string
    {
        return self::FORBIDDEN_DESCRIPTION;
    }
}
