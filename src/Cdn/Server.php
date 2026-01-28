<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Cdn;

/**
 * Class Server
 */
class Server implements CdnInterface
{
    public function __construct(
        protected string $path,
        protected array $paths = [],
    ) {
    }

    public function getPath(string $relativePath): string
    {
        // If we have provided array pick random one
        $theChosenOne = !empty($this->paths) ? $this->paths[array_rand($this->paths)] : $this->path;

        return \sprintf('%s/%s', rtrim($theChosenOne, '/'), ltrim($relativePath, '/'));
    }
}
