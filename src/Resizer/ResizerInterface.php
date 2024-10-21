<?php

namespace NetBull\MediaBundle\Resizer;

use Gaufrette\File;
use Imagine\Image\Box;
use NetBull\MediaBundle\Entity\MediaInterface;

interface ResizerInterface
{
    /**
     * @param MediaInterface $media
     * @param File $in
     * @param File $out
     * @param string $format
     * @param array $settings
     * @return void
     */
    public function resize(MediaInterface $media, File $in, File $out, string $format, array $settings): void;

    /**
     * @param MediaInterface $media
     * @param array $settings
     * @return Box
     */
    public function getBox(MediaInterface $media, array $settings): Box;
}
