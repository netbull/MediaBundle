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
     * @var MediaInterface|null
     */
    private ?MediaInterface $main = null;

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
     * @return MediaInterface|null
     */
    public function main(): ?MediaInterface
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
     * @return MediaInterface|null
     */
    private function findMain(): ?MediaInterface
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
        $this->rest = $rest->toArray();

        return $this->main;
    }
}
