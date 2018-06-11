<?php

namespace NetBull\MediaBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

use NetBull\MediaBundle\Model\MediaInterface;

/**
 * Class PublicDownloadStrategy
 * @package NetBull\MediaBundle\Security
 */
class PublicDownloadStrategy implements DownloadStrategyInterface
{
    protected $translator;

    /**
     * PublicDownloadStrategy constructor.
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
        return true;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->translator->trans('description.public_download_strategy', [], 'NetBullMediaBundle');
    }
}
