<?php

namespace NetBull\MediaBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use NetBull\MediaBundle\Entity\MediaInterface;

/**
 * Class PublicDownloadStrategy
 * @package NetBull\MediaBundle\Security
 */
class PublicDownloadStrategy implements DownloadStrategyInterface
{
    /**
     * @param MediaInterface $media
     * @param Request $request
     *
     * @return bool
     */
    public function isGranted(MediaInterface $media, Request $request)
    {
        return true;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return self::FORBIDDEN_DESCRIPTION;
    }
}
