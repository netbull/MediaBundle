<?php

namespace NetBull\MediaBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use NetBull\MediaBundle\Model\MediaInterface;

/**
 * Class SessionDownloadStrategy
 * @package NetBull\MediaBundle\Security
 */
class SessionDownloadStrategy implements DownloadStrategyInterface
{
    protected $container;

    protected $translator;

    protected $times;

    protected $sessionKey = 'SomethingReallyCool';

    /**
     * SessionDownloadStrategy constructor.
     * @param \Symfony\Component\Translation\TranslatorInterface        $translator
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     * @param int                                                       $times
     */
    public function __construct(TranslatorInterface $translator, ContainerInterface $container, $times)
    {
        $this->times      = $times;
        $this->container  = $container;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function isGranted(MediaInterface $media, Request $request)
    {
        if (!$this->container->has('session')) {
            return false;
        }

        $times = $this->getSession()->get($this->sessionKey, 0);

        if ($times >= $this->times) {
            return false;
        }

        ++$times;

        $this->getSession()->set($this->sessionKey, $times);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->translator->trans('description.session_download_strategy', ['%times%' => $this->times], 'NetBullMediaBundle');
    }

    /**
     * @return object
     */
    private function getSession()
    {
        return $this->container->get('session');
    }
}
