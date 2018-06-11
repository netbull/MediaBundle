<?php

namespace NetBull\MediaBundle\Metadata;

use NetBull\MediaBundle\Model\MediaInterface;

/**
 * Interface MetadataBuilderInterface
 * @package NetBull\MediaBundle\Metadata
 */
interface MetadataBuilderInterface
{
    /**
     * Get metadata for media object.
     *
     * @param MediaInterface $media
     * @param string         $filename
     *
     * @return array
     */
    public function get(MediaInterface $media, $filename);
}
