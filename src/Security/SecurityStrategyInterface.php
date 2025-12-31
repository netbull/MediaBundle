<?php

namespace NetBull\MediaBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use NetBull\MediaBundle\Entity\MediaInterface;

interface SecurityStrategyInterface
{
    const string FORBIDDEN_DESCRIPTION = 'This strategy is forbidden';

    /**
     * @abstract
     *
     * @param MediaInterface $media
     * @param Request $request
     *
     * @return bool
     */
    public function isGranted(MediaInterface $media, Request $request): bool;

    /**
     * @abstract
     *
     * @return string
     */
    public function getDescription(): string;
}
