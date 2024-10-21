<?php

namespace NetBull\MediaBundle\Repository;

interface ImageInterface
{
    /**
     * @param string $type
     * @param array $images
     * @return void
     */
    public function reorderImages(string $type, array $images): void;

    /**
     * @param mixed $object
     * @param bool $orderById
     * @return array|null
     */
    public function getImages(mixed $object, bool $orderById = false): ?array;

    /**
     * @param array $images
     * @return array
     */
    public function getImagesByIds(array $images): array;

    /**
     * @param mixed $object
     * @return int
     */
    public function getImageIndex(mixed $object): int;
}
