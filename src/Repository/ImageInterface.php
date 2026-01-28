<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Repository;

interface ImageInterface
{
    public function reorderImages(string $type, array $images): void;

    public function getImages(mixed $object, bool $orderById = false): ?array;

    public function getImagesByIds(array $images): array;

    public function getImageIndex(mixed $object): int;
}
