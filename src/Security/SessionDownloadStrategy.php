<?php

namespace NetBull\MediaBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use NetBull\MediaBundle\Entity\MediaInterface;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class SessionDownloadStrategy
 * @package NetBull\MediaBundle\Security
 */
class SessionDownloadStrategy implements DownloadStrategyInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var int
     */
    protected $times;

    /**
     * @var string
     */
    protected $sessionKey = 'SomethingReallyCool';

    /**
     * SessionDownloadStrategy constructor.
     * @param ContainerInterface $container
     * @param int $times
     */
    public function __construct(ContainerInterface $container, int $times)
    {
        $this->times = $times;
        $this->container = $container;
    }

    /**
     * @param MediaInterface $media
     * @param Request $request
     * @return bool
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
     * @return string
     */
    public function getDescription()
    {
        return self::FORBIDDEN_DESCRIPTION;
//        return $this->translator->trans('description.session_download_strategy', ['%times%' => $this->times], 'NetBullMediaBundle');
    }

    /**
     * @return object|Session|null
     */
    private function getSession()
    {
        return $this->container->get('session');
    }
}
