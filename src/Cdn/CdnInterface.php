<?php

namespace NetBull\MediaBundle\Cdn;

/**
 * Interface CdnInterface
 * @package NetBull\MediaBundle\Cdn
 */
interface CdnInterface
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
