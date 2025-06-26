<?php

namespace NetBull\MediaBundle\Cdn;

class LocalServer implements CdnInterface
{
    /**
     * @param string $path
     * @param string $devPath
     * @param string $localPath
     * @param array $paths
     */
    public function __construct(
        protected string $path,
        protected string $devPath,
        protected string $localPath,
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
