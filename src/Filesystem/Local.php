<?php

namespace NetBull\MediaBundle\Filesystem;

use Gaufrette\Adapter\Local as BaseLocal;

/**
 * Class Local
 * @package NetBull\MediaBundle\Filesystem
 */
class Local extends BaseLocal
{
    /**
     * @return mixed
     */
    public function getDirectory()
    {
        return $this->directory;
    }
}
