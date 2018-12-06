<?php

namespace NetBull\MediaBundle\Utils;

use Doctrine\Common\Collections\ArrayCollection;

use NetBull\MediaBundle\Model\MediaInterface;

/**
 * Class MediaCollection
 * @package NetBull\MediaBundle\Utils
 */
class MediaCollection extends ArrayCollection
{
    /**
     * @var bool
     */
    private $isArrayCollection = true;

    /**
     * MediaCollection constructor.
     * @param array $elements
     */
    public function __construct(array $elements = [])
    {
        parent::__construct($elements);

        if ($first = $this->first()) {
            if ($first instanceof MediaInterface) {
                $this->isArrayCollection = false;
            }
        }
    }

    /**
     * @return mixed
     */
    public function main()
    {
        $isArrayCollection = $this->isArrayCollection;
        $filtered = $this->filter(function ($media) use ($isArrayCollection) {
            return $isArrayCollection ? $media['main'] : $media->isMain();
        });

        if ($filtered->count()) {
            return $filtered->first();
        }

        return $this->first();
    }
}
