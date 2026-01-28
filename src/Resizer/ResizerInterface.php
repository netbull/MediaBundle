<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Resizer;

use Gaufrette\File;
use Imagine\Image\Box;
use NetBull\MediaBundle\Entity\MediaInterface;

interface ResizerInterface
{
    public function resize(MediaInterface $media, File $in, File $out, string $format, array $settings): void;

    public function getBox(MediaInterface $media, array $settings): Box;
}
