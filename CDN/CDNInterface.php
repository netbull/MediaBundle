<?php

namespace NetBull\MediaBundle\CDN;

/**
 * Interface CDNInterface
 * @package NetBull\MediaBundle\CDN
 */
interface CDNInterface
{

    /**
     * Return the base path.
     *
     * @param string $relativePath
     *
     * @return string
     */
    public function getPath($relativePath);
}
