<?php

namespace NetBull\MediaBundle\Cdn;

interface CdnInterface
{
    /**
     * Return the base path.
     *
     * @param string $relativePath
     *
     * @return string
     */
    public function getPath(string $relativePath): string;
}
