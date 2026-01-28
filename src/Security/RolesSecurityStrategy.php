<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Security;

use NetBull\MediaBundle\Entity\MediaInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class RolesSecurityStrategy implements SecurityStrategyInterface
{
    protected array $roles;

    protected AuthorizationChecker $security;

    public function __construct(AuthorizationChecker $security, array $roles = [])
    {
        $this->roles = $roles;
        $this->security = $security;
    }

    public function isGranted(MediaInterface $media, Request $request): bool
    {
        return array_any($this->roles, fn ($role) => $this->security->isGranted($role));
    }

    public function getDescription(): string
    {
        return self::FORBIDDEN_DESCRIPTION;
    }
}
