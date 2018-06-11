<?php

namespace NetBull\MediaBundle\Resizer;

use Gaufrette\File;

use NetBull\MediaBundle\Model\MediaInterface;

/**
 * Interface ResizerInterface
 * @package NetBull\MediaBundle\Resizer
 */
interface ResizerInterface
{
    /**
     * @param MediaInterface $media
     * @param File           $in
     * @param File           $out
     * @param string         $format
     * @param array          $settings
     */
    public function resize(MediaInterface $media, File $in, File $out, $format, array $settings);

    /**
     * @param MediaInterface $media
     * @param array          $settings
     *
     * @return \Imagine\Image\Box
     */
    public function getBox(MediaInterface $media, array $settings);
}
