<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Utils;

use Closure;
use Doctrine\Common\Collections\ArrayCollection;
use NetBull\MediaBundle\Entity\MediaInterface;

class MediaCollection extends ArrayCollection
{
    private bool $isArrayCollection = true;

    private array|MediaInterface|null $main = null;

    private array $rest = [];

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

    public function main(): array|MediaInterface|null
    {
        if ($this->main) {
            return $this->main;
        }

        return $this->findMain();
    }

    public function rest(): array
    {
        if (!empty($this->rest)) {
            return $this->rest;
        }

        $this->findMain();

        return $this->rest;
    }

    public function filterCustom(Closure $p): ArrayCollection
    {
        return new ArrayCollection(array_filter($this->toArray(), $p));
    }

    private function findMain(): array|MediaInterface|null
    {
        $isArrayCollection = $this->isArrayCollection;
        $filtered = $this->filterCustom(static function ($media) use ($isArrayCollection) {
            return $isArrayCollection ? $media['main'] : $media->isMain();
        });

        if ($filtered->count()) {
            return $filtered->first();
        }

        $main = $this->first();
        if (!$main) {
            return null;
        }

        $rest = $this->filterCustom(static function ($media) use ($isArrayCollection, $main) {
            $id = $isArrayCollection ? $media['id'] : $media->getId();
            $mainId = $isArrayCollection ? $main['id'] : $main->getId();

            return $id !== $mainId;
        });

        $this->main = $main;
        $this->rest = $rest->toArray();

        return $this->main;
    }
}
