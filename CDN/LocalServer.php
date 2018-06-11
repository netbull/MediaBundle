<?php

namespace NetBull\MediaBundle\CDN;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class LocalServer
 * @package NetBull\MediaBundle\CDN
 */
class LocalServer implements CDNInterface
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
     * @var string
     */
    protected $devPath;

    /**
     * @var string
     */
    protected $localPath;

    /**
     * LocalServer constructor.
     * @param string $path
     * @param array $paths
     * @param string $devPath
     * @param string $localPath
     * @param RequestStack $requestStack
     */
    public function __construct(string $path, array $paths = [], string $devPath, string $localPath, RequestStack $requestStack)
    {
        $this->path = $path;
        $this->paths = $paths;
        $this->localPath = $localPath;
        $this->devPath = $devPath;
    }

    /**
     * {@inheritDoc}
     */
    public function getPath($relativePath)
    {
        // If we have provided array pick random one
        $theChosenOne = !empty($this->paths) ? $this->paths[array_rand($this->paths)] : $this->path;

        $localMedia = sprintf('%s/%s', rtrim($this->localPath, '/'), ltrim($relativePath, '/'));

        if (file_exists($localMedia)) {
            $parts = explode('public', $this->localPath, 2);
            if (!empty($parts)) {
                $theChosenOne = $this->devPath . $parts[1];
            }
        }
        return sprintf('%s/%s', rtrim($theChosenOne, '/'), ltrim($relativePath, '/'));
    }
}
