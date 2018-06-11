<?php

namespace NetBull\MediaBundle\Listener;

use Doctrine\ORM\Events;
use Doctrine\Common\EventArgs;
use Doctrine\Common\EventSubscriber;

use NetBull\MediaBundle\Provider\Pool;
use NetBull\MediaBundle\Model\MediaInterface;
use NetBull\MediaBundle\Provider\MediaProviderInterface;

/**
 * Class MediaEventSubscriber
 * @package NetBull\MediaBundle\Listener
 */
class MediaEventSubscriber implements EventSubscriber
{
    /**
     * @var Pool
     */
    private $pool;

    /**
     * MediaEventSubscriber constructor.
     * @param Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * @return Pool
     */
    public function getPool()
    {
        return $this->pool;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
            Events::preUpdate,
            Events::preRemove,
            Events::postUpdate,
            Events::postRemove,
            Events::postPersist,
        ];
    }

    /**
     * @param EventArgs $args
     */
    protected function recomputeSingleEntityChangeSet(EventArgs $args)
    {
        $em = $args->getEntityManager();

        $em->getUnitOfWork()->recomputeSingleEntityChangeSet(
            $em->getClassMetadata(get_class($args->getEntity())),
            $args->getEntity()
        );
    }

    /**
     * @param EventArgs $args
     * @return mixed
     */
    protected function getMedia(EventArgs $args)
    {
        return $args->getEntity();
    }

    /**
     * @param EventArgs $args
     * @return null|MediaProviderInterface
     */
    protected function getProvider(EventArgs $args)
    {
        $media = $this->getMedia($args);

        if (!$media instanceof MediaInterface) {
            return null;
        }

        return $this->getPool()->getProvider($media->getProviderName());
    }

    /**
     * @param EventArgs $args
     */
    public function postUpdate(EventArgs $args)
    {
        if (!($provider = $this->getProvider($args))) {
            return;
        }

        $provider->postUpdate($this->getMedia($args));
    }

    /**
     * @param EventArgs $args
     */
    public function postRemove(EventArgs $args)
    {
        if (!($provider = $this->getProvider($args))) {
            return;
        }

        $provider->postRemove($this->getMedia($args));
    }

    /**
     * @param EventArgs $args
     */
    public function postPersist(EventArgs $args)
    {
        if (!($provider = $this->getProvider($args))) {
            return;
        }

        $provider->postPersist($this->getMedia($args));
    }

    /**
     * @param EventArgs $args
     */
    public function preUpdate(EventArgs $args)
    {
        if (!($provider = $this->getProvider($args))) {
            return;
        }

        $provider->transform($this->getMedia($args));
        $provider->preUpdate($this->getMedia($args));

        $this->recomputeSingleEntityChangeSet($args);
    }

    /**
     * @param EventArgs $args
     */
    public function preRemove(EventArgs $args)
    {
        if (!($provider = $this->getProvider($args))) {
            return;
        }

        $provider->preRemove($this->getMedia($args));
    }

    /**
     * @param EventArgs $args
     */
    public function prePersist(EventArgs $args)
    {
        if (!($provider = $this->getProvider($args))) {
            return;
        }

        $provider->transform($this->getMedia($args));
        $provider->prePersist($this->getMedia($args));
    }
}
