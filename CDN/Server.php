<?php

namespace NetBull\MediaBundle\CDN;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class Server
 * @package NetBull\MediaBundle\CDN
 */
class Server implements CDNInterface
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
     * @param string        $path
     * @param array         $paths
     * @param RequestStack  $requestStack
     */
    public function __construct($path, array $paths = [], RequestStack $requestStack)
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
