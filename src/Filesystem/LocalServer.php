<?php

namespace NetBull\MediaBundle\Filesystem;

use Gaufrette\File;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\AwsS3;

class LocalServer extends Filesystem
{
    /**
     * @param Local $local
     * @param AwsS3 $remote
     */
    public function __construct(
        private Local $local,
        private AwsS3 $remote
    ) {
        parent::__construct($local);

        $this->adapter = $local;
    }

    /**
     * @param $key
     * @param bool $create
     * @return File|mixed
     */
    public function get($key, $create = false): mixed
    {
        $this->has($key);

        return parent::get($key, $create);
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key): bool
    {
        if ($this->local->exists($key)) {
            $this->adapter = $this->local;
            return true;
        }

        if ($this->remote->exists($key)) {
            $this->adapter = $this->remote;
            return true;
        }

        return false;
    }
}
