<?php

namespace NetBull\MediaBundle\Cdn;

/**
 * Class Server
 * @package NetBull\MediaBundle\Cdn
 */
class Server implements CdnInterface
{
    /**
     * @param string $path
     * @param array $paths
     */
    public function __construct(
        protected string $path,
        protected array $paths = []
    ) {
    }

    /**
     * @param string $relativePath
     * @return string
     */
    public function getPath(string $relativePath): string
    {
        // If we have provided array pick random one
        $theChosenOne = !empty($this->paths) ? $this->paths[array_rand($this->paths)] : $this->path;
        return sprintf('%s/%s', rtrim($theChosenOne, '/'), ltrim($relativePath, '/'));
    }
}
