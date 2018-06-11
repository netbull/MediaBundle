<?php

namespace NetBull\MediaBundle\Security;

use Symfony\Component\HttpFoundation\Request;

use NetBull\MediaBundle\Model\MediaInterface;

/**
 * Interface DownloadStrategyInterface
 * @package NetBull\MediaBundle\Security
 */
interface DownloadStrategyInterface
{
    /**
     * @abstract
     *
     * @param MediaInterface    $media
     * @param Request           $request
     *
     * @return bool
     */
    public function isGranted(MediaInterface $media, Request $request);

    /**
     * @abstract
     *
     * @return string
     */
    public function getDescription();
}
