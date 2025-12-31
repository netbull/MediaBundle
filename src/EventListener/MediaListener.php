<?php

namespace NetBull\MediaBundle\EventListener;

use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use NetBull\MediaBundle\Provider\Pool;
use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Provider\MediaProviderInterface;

class MediaListener
{
    /**
     * @var ArrayCollection
     */
    private ArrayCollection $medias;

    /**
     * @param Pool $pool
     */
    public function __construct(private readonly Pool $pool)
    {
        $this->medias = new ArrayCollection();
    }

    /**
     * @param PrePersistEventArgs $args
     * @return void
     */
    public function prePersist(PrePersistEventArgs $args): void
    {
        if (!$provider = $this->getProvider($args)) {
            return;
        }

        $provider->transform($this->getMedia($args));
        $provider->prePersist($this->getMedia($args));
    }

    /**
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        if (!$provider = $this->getProvider($args)) {
            return;
        }

        $provider->transform($this->getMedia($args));
        $provider->preUpdate($this->getMedia($args));

        $em = $args->getObjectManager();

        $em->getUnitOfWork()->recomputeSingleEntityChangeSet(
            $em->getClassMetadata(get_class($args->getObject())),
            $args->getObject()
        );
    }

    /**
     * @param PreRemoveEventArgs $args
     */
    public function preRemove(PreRemoveEventArgs $args): void
    {
        if (!$provider = $this->getProvider($args)) {
            return;
        }

        $provider->preRemove($this->getMedia($args));
    }

    /**
     * @param PostPersistEventArgs $args
     */
    public function postPersist(PostPersistEventArgs $args): void
    {
        if (!$provider = $this->getProvider($args)) {
            return;
        }

        $provider->postPersist($this->getMedia($args));
    }

    /**
     * @param PostUpdateEventArgs $args
     */
    public function postUpdate(PostUpdateEventArgs $args): void
    {
        if (!$provider = $this->getProvider($args)) {
            return;
        }

        $provider->postUpdate($this->getMedia($args));
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args): void
    {
        if (!$provider = $this->getProvider($args)) {
            return;
        }

        $provider->postRemove($this->getMedia($args));
    }

    public function postFlush(): void
    {
        foreach ($this->medias as $media) {
            if (!$provider = $this->getProviderByMedia($media)) {
                continue;
            }
            $provider->postFlush($media);
        }
        $this->medias->clear();
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

        return $this->pool->getProvider($media->getProviderName());
    }
}
