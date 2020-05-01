<?php

namespace NetBull\MediaBundle\Metadata;

/**
 * Interface MetadataBuilderInterface
 * @package NetBull\MediaBundle\Metadata
 */
interface MetadataBuilderInterface
{
    /**
     * Get metadata for media object.
     *
     * @param string $filename
     *
     * @return array
     */
    public function get($filename);
}
