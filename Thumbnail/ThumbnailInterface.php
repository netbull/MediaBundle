<?php

namespace NetBull\MediaBundle\Thumbnail;

use NetBull\MediaBundle\Model\MediaInterface;
use NetBull\MediaBundle\Provider\MediaProviderInterface;

/**
 * Interface ThumbnailInterface
 * @package NetBull\MediaBundle\Thumbnail
 */
interface ThumbnailInterface
{
    /**
     * @param MediaProviderInterface    $provider
     * @param array|MediaInterface      $media
     * @param string                    $format
     */
    public function generatePublicUrl(MediaProviderInterface $provider, $media, $format);

    /**
     * @param MediaProviderInterface    $provider
     * @param MediaInterface            $media
     * @param string                    $format
     */
    public function generatePrivateUrl(MediaProviderInterface $provider, MediaInterface $media, $format);

    /**
     * @param MediaProviderInterface    $provider
     * @param MediaInterface            $media
     */
    public function generate(MediaProviderInterface $provider, MediaInterface $media);

    /**
     * @param MediaProviderInterface    $provider
     * @param MediaInterface            $media
     * @param string                    $format
     * @return mixed
     */
    public function generateByFormat(MediaProviderInterface $provider, MediaInterface $media, $format);

    /**
     * @param MediaProviderInterface    $provider
     * @param MediaInterface            $media
     */
    public function delete(MediaProviderInterface $provider, MediaInterface $media);
}
