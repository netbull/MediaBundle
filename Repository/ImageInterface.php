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
     * @return mixed
     */
    public function reorderImages($type, $images);

    /**
     * @param $object
     * @return mixed
     */
    public function getImages($object);

    /**
     * @param $images
     * @return mixed
     */
    public function getImagesByIds($images);

    /**
     * @param $object
     * @return mixed
     */
    public function getImageIndex($object);
}
