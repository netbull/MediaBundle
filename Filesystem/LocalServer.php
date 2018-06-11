<?php

namespace NetBull\MediaBundle\Filesystem;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\AwsS3;

/**
 * Class LocalServer
 * @package NetBull\MediaBundle\Filesystem
 */
class LocalServer extends Filesystem
{
    /**
     * @var Filesystem
     */
    private $local;

    /**
     * @var Filesystem
     */
    private $remote;

    /**
     * LocalServer constructor.
     * @param Filesystem $local
     * @param Filesystem $remote
     */
    public function __construct(Local $local, AwsS3 $remote)
    {
        $this->local = $local;
        $this->remote = $remote;

        $this->adapter = $local;
    }

    /**
     * {@inheritdoc}
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
    public function has($key)
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
