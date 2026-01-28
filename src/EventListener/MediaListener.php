<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Provider\MediaProviderInterface;
use NetBull\MediaBundle\Provider\Pool;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
#[AsDoctrineListener(event: Events::preRemove)]
#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postRemove)]
#[AsDoctrineListener(event: Events::postFlush)]
class MediaListener
{
    private ArrayCollection $medias;

    public function __construct(private readonly Pool $pool)
    {
        $this->medias = new ArrayCollection();
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        if (!$provider = $this->getProvider($args)) {
            return;
        }

        $provider->transform($this->getMedia($args));
        $provider->prePersist($this->getMedia($args));
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        if (!$provider = $this->getProvider($args)) {
            return;
        }

        $provider->transform($this->getMedia($args));
        $provider->preUpdate($this->getMedia($args));

        $em = $args->getObjectManager();

        $em->getUnitOfWork()->recomputeSingleEntityChangeSet(
            $em->getClassMetadata(\get_class($args->getObject())),
            $args->getObject(),
        );
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        if (!$provider = $this->getProvider($args)) {
            return;
        }

        $provider->preRemove($this->getMedia($args));
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        if (!$provider = $this->getProvider($args)) {
            return;
        }

        $provider->postPersist($this->getMedia($args));
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        if (!$provider = $this->getProvider($args)) {
            return;
        }

        $provider->postUpdate($this->getMedia($args));
    }

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

    protected function getProvider(LifecycleEventArgs $args): ?MediaProviderInterface
    {
        $media = $this->getMedia($args);

        return $this->getProviderByMedia($media);
    }

    protected function getProviderByMedia($media): ?MediaProviderInterface
    {
        if (!$media instanceof MediaInterface) {
            return null;
        }

        return $this->pool->getProvider($media->getProviderName());
    }
}
