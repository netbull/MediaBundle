<?php

namespace NetBull\MediaBundle\Metadata;

interface MetadataBuilderInterface
{
    /**
     * @param string $filename
     * @return array
     */
    public function get(string $filename): array;
}
