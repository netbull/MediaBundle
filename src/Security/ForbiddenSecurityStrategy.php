<?php

namespace NetBull\MediaBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use NetBull\MediaBundle\Entity\MediaInterface;

class ForbiddenSecurityStrategy implements SecurityStrategyInterface
{
    /**
     * @param MediaInterface $media
     * @param Request $request
     *
     * @return bool
     */
    public function isGranted(MediaInterface $media, Request $request): bool
    {
        return false;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return self::FORBIDDEN_DESCRIPTION;
    }
}