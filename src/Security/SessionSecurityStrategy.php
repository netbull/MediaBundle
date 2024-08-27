<?php

namespace NetBull\MediaBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use NetBull\MediaBundle\Entity\MediaInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class SessionSecurityStrategy implements SecurityStrategyInterface
{
    /**
     * @var RequestStack
     */
    protected RequestStack $requestStack;

    /**
     * @var int
     */
    protected int $times;

    /**
     * @var string
     */
    protected string $sessionKey = 'SomethingReallyCool';

    /**
     * @param RequestStack $requestStack
     * @param int $times
     */
    public function __construct(RequestStack $requestStack, int $times)
    {
        $this->times = $times;
        $this->requestStack = $requestStack;
    }

    /**
     * @param MediaInterface $media
     * @param Request $request
     * @return bool
     */
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

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return self::FORBIDDEN_DESCRIPTION;
    }
}
