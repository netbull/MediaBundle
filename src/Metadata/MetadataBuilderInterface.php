<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Metadata;

interface MetadataBuilderInterface
{
    public function get(string $filename): array;
}
