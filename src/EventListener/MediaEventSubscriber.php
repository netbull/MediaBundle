<?php

namespace NetBull\MediaBundle\EventListener;

use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use NetBull\MediaBundle\Provider\Pool;
use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Provider\MediaProviderInterface;

/**
 * Class MediaEventSubscriber
 * @package NetBull\MediaBundle\EventListener
 */
class MediaEventSubscriber implements EventSubscriber
{
    /**
     * @var Pool
     */
    private $pool;

    /**
     * @var ArrayCollection
     */
    private $medias;

    /**
     * MediaEventSubscriber constructor.
     * @param Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
        $this->medias = new ArrayCollection();
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
            Events::postFlush,
        ];
    }

    /**
     * @param LifecycleEventArgs $args
     */
    protected function recomputeSingleEntityChangeSet(LifecycleEventArgs $args)
    {
        $em = $args->getObjectManager();

        $em->getUnitOfWork()->recomputeSingleEntityChangeSet(
            $em->getClassMetadata(get_class($args->getObject())),
            $args->getObject()
        );
    }

    /**
     * @param LifecycleEventArgs $args
     * @return mixed
     */
    protected function getMedia(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        if (!$this->medias->contains($entity)) {
            $this->medias->add($entity);
        }

        return $entity;
    }

    /**
     * @param LifecycleEventArgs $args
     * @return null|MediaProviderInterface
     */
    protected function getProvider(LifecycleEventArgs $args)
    {
        $media = $this->getMedia($args);

        return $this->getProviderByMedia($media);
    }

    /**
     * @param $media
     * @return MediaProviderInterface|null
     */
    protected function getProviderByMedia($media)
    {
        if (!$media instanceof MediaInterface) {
            return null;
        }

        return $this->getPool()->getProvider($media->getProviderName());
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        if (!($provider = $this->getProvider($args))) {
            return;
        }

        $provider->postUpdate($this->getMedia($args));
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        if (!($provider = $this->getProvider($args))) {
            return;
        }

        $provider->postRemove($this->getMedia($args));
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        if (!($provider = $this->getProvider($args))) {
            return;
        }

        $provider->postPersist($this->getMedia($args));
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        foreach ($this->medias as $media) {
            if (!($provider = $this->getProviderByMedia($media))) {
                continue;
            }
            $provider->postFlush($media);
        }
        $this->medias->clear();
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preUpdate(LifecycleEventArgs $args)
    {
        if (!($provider = $this->getProvider($args))) {
            return;
        }

        $provider->transform($this->getMedia($args));
        $provider->preUpdate($this->getMedia($args));

        $this->recomputeSingleEntityChangeSet($args);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        if (!($provider = $this->getProvider($args))) {
            return;
        }

        $provider->preRemove($this->getMedia($args));
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        if (!($provider = $this->getProvider($args))) {
            return;
        }

        $provider->transform($this->getMedia($args));
        $provider->prePersist($this->getMedia($args));
    }
}
