<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Filesystem;

use Gaufrette\Adapter\Local as BaseLocal;

class Local extends BaseLocal
{
    public function getDirectory(): string
    {
        return $this->directory;
    }
}
