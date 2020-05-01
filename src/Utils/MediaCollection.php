<?php

namespace NetBull\MediaBundle\Utils;

use Closure;
use Doctrine\Common\Collections\ArrayCollection;
use NetBull\MediaBundle\Entity\MediaInterface;

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

        if (!empty($elements)) {
            $this->findMain();
        }
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
     * @param Closure $p
     * @return ArrayCollection
     */
    public function filterCustom(Closure $p)
    {
        return new ArrayCollection(array_filter($this->toArray(), $p));
    }

    /**
     * @return mixed
     */
    private function findMain()
    {
        $isArrayCollection = $this->isArrayCollection;
        $filtered = $this->filterCustom(function ($media) use ($isArrayCollection) {
            return $isArrayCollection ? $media['main'] : $media->isMain();
        });

        if ($filtered->count()) {
            return $filtered->first();
        }

        $main = $this->first();

        $rest = $this->filterCustom(function ($media) use ($isArrayCollection, $main) {
            $id = $isArrayCollection ? $media['id'] : $media->getId();
            $mainId = $isArrayCollection ? $main['id'] : $main->getId();

            return $id !== $mainId;
        });

        $this->main = $main;
        $this->rest = $rest;

        return $this->main;
    }
}
