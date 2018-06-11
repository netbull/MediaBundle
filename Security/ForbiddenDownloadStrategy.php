<?php

namespace NetBull\MediaBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

use NetBull\MediaBundle\Model\MediaInterface;

/**
 * Class ForbiddenDownloadStrategy
 * @package NetBull\MediaBundle\Security
 */
class ForbiddenDownloadStrategy implements DownloadStrategyInterface
{
    protected $translator;

    /**
     * ForbiddenDownloadStrategy constructor.
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param MediaInterface    $media
     * @param Request           $request
     *
     * @return bool
     */
    public function isGranted(MediaInterface $media, Request $request)
    {
        return false;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->translator->trans('description.forbidden_download_strategy', [], 'NetBullMediaBundle');
    }
}
