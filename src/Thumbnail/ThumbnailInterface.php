<?php

namespace NetBull\MediaBundle\Thumbnail;

use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Provider\MediaProviderInterface;

/**
 * Interface ThumbnailInterface
 * @package NetBull\MediaBundle\Thumbnail
 */
interface ThumbnailInterface
{
    /**
     * @param MediaProviderInterface $provider
     * @param array|MediaInterface $media
     * @param string $format
     * @return string
     */
    public function generatePublicUrl(MediaProviderInterface $provider, $media, string $format);

    /**
     * @param MediaProviderInterface $provider
     * @param MediaInterface $media
     * @param string $format
     * @return string
     */
    public function generatePrivateUrl(MediaProviderInterface $provider, MediaInterface $media, string $format);

    /**
     * @param MediaProviderInterface $provider
     * @param MediaInterface $media
     * @return string
     */
    public function generate(MediaProviderInterface $provider, MediaInterface $media);

    /**
     * @param MediaProviderInterface $provider
     * @param MediaInterface $media
     * @param string $format
     * @return string
     */
    public function generateByFormat(MediaProviderInterface $provider, MediaInterface $media, string $format);

    /**
     * @param MediaProviderInterface $provider
     * @param MediaInterface $media
     * @return string
     */
    public function delete(MediaProviderInterface $provider, MediaInterface $media);
}
