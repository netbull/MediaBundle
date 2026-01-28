<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Thumbnail;

use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Provider\MediaProviderInterface;

interface ThumbnailInterface
{
    public function generatePublicUrl(MediaProviderInterface $provider, array|MediaInterface $media, string $format): string;

    public function generatePrivateUrl(MediaProviderInterface $provider, MediaInterface $media, string $format): string;

    public function generate(MediaProviderInterface $provider, MediaInterface $media): void;

    public function generateByFormat(MediaProviderInterface $provider, MediaInterface $media, string $format): void;

    public function delete(MediaProviderInterface $provider, MediaInterface $media): void;
}
