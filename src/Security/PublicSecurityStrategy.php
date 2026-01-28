<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Security;

use NetBull\MediaBundle\Entity\MediaInterface;
use Symfony\Component\HttpFoundation\Request;

class PublicSecurityStrategy implements SecurityStrategyInterface
{
    public function isGranted(MediaInterface $media, Request $request): bool
    {
        return true;
    }

    public function getDescription(): string
    {
        return self::FORBIDDEN_DESCRIPTION;
    }
}
