<?php

namespace NetBull\MediaBundle\Filesystem;

use Gaufrette\Adapter\Local as BaseLocal;

class Local extends BaseLocal
{
    /**
     * @return string
     */
    public function getDirectory(): string
    {
        return $this->directory;
    }
}
