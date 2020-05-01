<?php

namespace NetBull\MediaBundle\Cdn;

/**
 * Class Server
 * @package NetBull\MediaBundle\Cdn
 */
class Server implements CdnInterface
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var array
     */
    protected $paths;

    /**
     * Server constructor.
     * @param string $path
     * @param array $paths
     */
    public function __construct(string $path, array $paths = [])
    {
        $this->path = $path;
        $this->paths = $paths;
    }

    /**
     * {@inheritDoc}
     */
    public function getPath($relativePath)
    {
        // If we have provided array pick random one
        $theChosenOne = !empty($this->paths) ? $this->paths[array_rand($this->paths)] : $this->path;
        return sprintf('%s/%s', rtrim($theChosenOne, '/'), ltrim($relativePath, '/'));
    }
}
