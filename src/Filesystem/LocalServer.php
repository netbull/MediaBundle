<?php

namespace NetBull\MediaBundle\Filesystem;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\AwsS3;

class LocalServer extends Filesystem
{
    /**
     * @var Filesystem|Local
     */
    private Filesystem|Local $local;

    /**
     * @var AwsS3|Filesystem
     */
    private AwsS3|Filesystem $remote;

    /**
     * @param Local $local
     * @param AwsS3 $remote
     */
    public function __construct(Local $local, AwsS3 $remote)
    {
        parent::__construct($local);

        $this->local = $local;
        $this->remote = $remote;

        $this->adapter = $local;
    }

    /**
     * @param $key
     * @param bool $create
     * @return \Gaufrette\File|mixed
     */
    public function get($key, $create = false)
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
