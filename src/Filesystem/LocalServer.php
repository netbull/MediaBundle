<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Filesystem;

use Gaufrette\Adapter\AwsS3;
use Gaufrette\File;
use Gaufrette\Filesystem;

class LocalServer extends Filesystem
{
    public function __construct(
        private readonly Local $local,
        private readonly AwsS3 $remote,
    ) {
        parent::__construct($local);

        $this->adapter = $local;
    }

    /**
     * @param bool $create
     *
     * @return File|mixed
     */
    public function get($key, $create = false): mixed
    {
        $this->has($key);

        return parent::get($key, $create);
    }

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
