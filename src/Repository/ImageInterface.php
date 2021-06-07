<?php

namespace NetBull\MediaBundle\Repository;

/**
 * Interface ImageInterface
 * @package NetBull\MediaBundle\Repository
 */
interface ImageInterface
{
    /**
     * @param $type
     * @param $images
     */
    public function reorderImages($type, $images);

    /**
     * @param $object
     * @param bool $orderById
     * @return array|null
     */
    public function getImages($object, bool $orderById = false): ?array;

    /**
     * @param $images
     * @return array
     */
    public function getImagesByIds($images): array;

    /**
     * @param $object
     * @return int
     */
    public function getImageIndex($object): int;
}
