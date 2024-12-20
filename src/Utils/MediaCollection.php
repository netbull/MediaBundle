<?php

namespace NetBull\MediaBundle\Utils;

use Closure;
use Doctrine\Common\Collections\ArrayCollection;
use NetBull\MediaBundle\Entity\MediaInterface;

class MediaCollection extends ArrayCollection
{
    /**
     * @var bool
     */
    private bool $isArrayCollection = true;

    /**
     * @var array|MediaInterface|null
     */
    private array|MediaInterface|null $main = null;

    /**
     * @var array
     */
    private array $rest = [];

    /**
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
     * @return array|MediaInterface|null
     */
    public function main(): array|MediaInterface|null
    {
        if ($this->main) {
            return $this->main;
        }

        return $this->findMain();
    }

    /**
     * @return array
     */
    public function rest(): array
    {
        if (!empty($this->rest)) {
            return $this->rest;
        }

        $this->findMain();

        return $this->rest;
    }

    /**
     * @param Closure $p
     * @return ArrayCollection
     */
    public function filterCustom(Closure $p): ArrayCollection
    {
        return new ArrayCollection(array_filter($this->toArray(), $p));
    }

    /**
     * @return array|MediaInterface|null
     */
    private function findMain(): array|MediaInterface|null
    {
        $isArrayCollection = $this->isArrayCollection;
        $filtered = $this->filterCustom(function ($media) use ($isArrayCollection) {
            return $isArrayCollection ? $media['main'] : $media->isMain();
        });

        if ($filtered->count()) {
            return $filtered->first();
        }

        $main = $this->first();
        if (!$main) {
            return null;
        }

        $rest = $this->filterCustom(function ($media) use ($isArrayCollection, $main) {
            $id = $isArrayCollection ? $media['id'] : $media->getId();
            $mainId = $isArrayCollection ? $main['id'] : $main->getId();

            return $id !== $mainId;
        });

        $this->main = $main;
        $this->rest = $rest->toArray();

        return $this->main;
    }
}
