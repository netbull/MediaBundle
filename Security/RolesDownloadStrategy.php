<?php

namespace NetBull\MediaBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use NetBull\MediaBundle\Model\MediaInterface;

/**
 * Class RolesDownloadStrategy
 * @package NetBull\MediaBundle\Security
 */
class RolesDownloadStrategy implements DownloadStrategyInterface
{
    /**
     * @var array
     */
    protected $roles;

    /**
     * @var AuthorizationChecker
     */
    protected $security;

    /**
     * RolesDownloadStrategy constructor.
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
    public function isGranted(MediaInterface $media, Request $request)
    {
        return $this->security->isGranted($this->roles);
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return self::FORBIDDEN_DESCRIPTION;
//        return $this->translator->trans('description.roles_download_strategy', ['%roles%' => '<code>'.implode('</code>, <code>', $this->roles).'</code>'], 'NetBullMediaBundle');
    }
}
