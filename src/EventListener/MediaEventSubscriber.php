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

class MediaEventSubscriber implements EventSubscriber
{
    /**
     * @var Pool
     */
    private Pool $pool;

    /**
     * @var ArrayCollection
     */
    private ArrayCollection $medias;

    /**
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
    public function getPool(): Pool
    {
        return $this->pool;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents(): array
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
    protected function recomputeSingleEntityChangeSet(LifecycleEventArgs $args): void
    {
        $em = $args->getObjectManager();

        $em->getUnitOfWork()->recomputeSingleEntityChangeSet(
            $em->getClassMetadata(get_class($args->getObject())),
            $args->getObject()
        );
    }

    /**
     * @param LifecycleEventArgs $args
     * @return MediaInterface|null
     */
    protected function getMedia(LifecycleEventArgs $args): ?MediaInterface
    {
        /** @var MediaInterface $entity */
        $entity = $args->getObject();
        if (!$entity instanceof MediaInterface) {
            return null;
        }

        if (!$this->medias->contains($entity)) {
            $this->medias->add($entity);
        }

        return $entity;
    }

    /**
     * @param LifecycleEventArgs $args
     * @return MediaProviderInterface|null
     */
    protected function getProvider(LifecycleEventArgs $args): ?MediaProviderInterface
    {
        $media = $this->getMedia($args);

        return $this->getProviderByMedia($media);
    }

    /**
     * @param $media
     * @return MediaProviderInterface|null
     */
    protected function getProviderByMedia($media): ?MediaProviderInterface
    {
        if (!$media instanceof MediaInterface) {
            return null;
        }

        return $this->getPool()->getProvider($media->getProviderName());
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args): void
    {
        if (!($provider = $this->getProvider($args))) {
            return;
        }

        $provider->postUpdate($this->getMedia($args));
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args): void
    {
        if (!($provider = $this->getProvider($args))) {
            return;
        }

        $provider->postRemove($this->getMedia($args));
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args): void
    {
        if (!($provider = $this->getProvider($args))) {
            return;
        }

        $provider->postPersist($this->getMedia($args));
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args): void
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
    public function preUpdate(LifecycleEventArgs $args): void
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
    public function preRemove(LifecycleEventArgs $args): void
    {
        if (!($provider = $this->getProvider($args))) {
            return;
        }

        $provider->preRemove($this->getMedia($args));
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args): void
    {
        if (!($provider = $this->getProvider($args))) {
            return;
        }

        $provider->transform($this->getMedia($args));
        $provider->prePersist($this->getMedia($args));
    }
}
