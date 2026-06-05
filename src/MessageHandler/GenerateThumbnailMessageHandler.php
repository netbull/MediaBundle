<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\MessageHandler;

use Doctrine\ORM\EntityManagerInterface;
use NetBull\MediaBundle\Entity\Media;
use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Message\GenerateThumbnailMessage;
use NetBull\MediaBundle\Provider\Pool;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GenerateThumbnailMessageHandler
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Pool $pool,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function __invoke(GenerateThumbnailMessage $message): void
    {
        /** @var MediaInterface|null $media */
        $media = $this->em->getRepository(Media::class)->find($message->getMediaId());

        if (null === $media) {
            $this->logger->warning('[netbull_media] Cannot generate thumbnail: media {id} not found', [
                'id' => $message->getMediaId(),
            ]);

            return;
        }

        $provider = $this->pool->getProvider($media->getProviderName());
        $provider->generateThumbnail($media, $message->getFormat());
    }
}
