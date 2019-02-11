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

    private $main;
    private $rest;

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

        $this->findMain();
    }

    /**
     * @return mixed
     */
    public function main()
    {
        if ($this->main) {
            return $this->main;
        }

        return $this->findMain();
    }

    /**
     * @return mixed
     */
    public function rest()
    {
        if ($this->rest) {
            return $this->rest;
        }

        $this->findMain();

        return $this->rest;
    }

    /**
     * @return mixed
     */
    private function findMain()
    {
        $isArrayCollection = $this->isArrayCollection;
        $filtered = $this->filter(function ($media) use ($isArrayCollection) {
            return $isArrayCollection ? $media['main'] : $media->isMain();
        });

        if ($filtered->count()) {
            return $filtered->first();
        }

        $main = $this->first();

        $rest = $this->filter(function ($media) use ($isArrayCollection, $main) {
            $id = $isArrayCollection ? $media['id'] : $media->getId();
            $mainId = $isArrayCollection ? $main['id'] : $main->getId();

            return $id !== $mainId;
        });

        $this->main = $main;
        $this->rest = $rest;

        return $this->main;
    }
}
