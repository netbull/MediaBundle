<?php

namespace NetBull\MediaBundle\Thumbnail;

use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Provider\MediaProviderInterface;

interface ThumbnailInterface
{
    /**
     * @param MediaProviderInterface $provider
     * @param array|MediaInterface $media
     * @param string $format
     * @return string
     */
    public function generatePublicUrl(MediaProviderInterface $provider, array|MediaInterface $media, string $format): string;

    /**
     * @param MediaProviderInterface $provider
     * @param MediaInterface $media
     * @param string $format
     * @return string
     */
    public function generatePrivateUrl(MediaProviderInterface $provider, MediaInterface $media, string $format): string;

    /**
     * @param MediaProviderInterface $provider
     * @param MediaInterface $media
     * @return void
     */
    public function generate(MediaProviderInterface $provider, MediaInterface $media): void;

    /**
     * @param MediaProviderInterface $provider
     * @param MediaInterface $media
     * @param string $format
     * @return void
     */
    public function generateByFormat(MediaProviderInterface $provider, MediaInterface $media, string $format): void;

    /**
     * @param MediaProviderInterface $provider
     * @param MediaInterface $media
     * @return void
     */
    public function delete(MediaProviderInterface $provider, MediaInterface $media): void;
}
