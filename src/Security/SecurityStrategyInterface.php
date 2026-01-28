<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Security;

use NetBull\MediaBundle\Entity\MediaInterface;
use Symfony\Component\HttpFoundation\Request;

interface SecurityStrategyInterface
{
    public const string FORBIDDEN_DESCRIPTION = 'This strategy is forbidden';

    /**
     * @abstract
     */
    public function isGranted(MediaInterface $media, Request $request): bool;

    /**
     * @abstract
     */
    public function getDescription(): string;
}
