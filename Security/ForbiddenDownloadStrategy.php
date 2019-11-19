<?php

namespace NetBull\MediaBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use NetBull\MediaBundle\Model\MediaInterface;

/**
 * Class ForbiddenDownloadStrategy
 * @package NetBull\MediaBundle\Security
 */
class ForbiddenDownloadStrategy implements DownloadStrategyInterface
{
    /**
     * @param MediaInterface $media
     * @param Request $request
     *
     * @return bool
     */
    public function isGranted(MediaInterface $media, Request $request)
    {
        return false;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return self::FORBIDDEN_DESCRIPTION;
    }
}
