<?php

namespace NetBull\MediaBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use NetBull\MediaBundle\Entity\MediaInterface;

class RolesSecurityStrategy implements SecurityStrategyInterface
{
    /**
     * @var array
     */
    protected array $roles;

    /**
     * @var AuthorizationChecker
     */
    protected AuthorizationChecker $security;

    /**
     * @param AuthorizationChecker $security
     * @param array $roles
     */
    public function __construct(AuthorizationChecker $security, array $roles = [])
    {
        $this->roles = $roles;
        $this->security = $security;
    }

    /**
     * @param MediaInterface $media
     * @param Request $request
     *
     * @return bool
     */
    public function isGranted(MediaInterface $media, Request $request): bool
    {
        foreach ($this->roles as $role) {
            if ($this->security->isGranted($role)) {
                return true;
            }
        }

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
