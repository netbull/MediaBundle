<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Cdn;

interface CdnInterface
{
    /**
     * Return the base path.
     */
    public function getPath(string $relativePath): string;
}
