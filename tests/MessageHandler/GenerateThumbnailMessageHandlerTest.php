<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Tests\MessageHandler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use NetBull\MediaBundle\Entity\Media;
use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Message\GenerateThumbnailMessage;
use NetBull\MediaBundle\MessageHandler\GenerateThumbnailMessageHandler;
use NetBull\MediaBundle\Provider\MediaProviderInterface;
use NetBull\MediaBundle\Provider\Pool;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GenerateThumbnailMessageHandler::class)]
class GenerateThumbnailMessageHandlerTest extends TestCase
{
    public function testGeneratesThumbnailForTheRequestedFormat(): void
    {
        $media = $this->createMock(MediaInterface::class);
        $media->method('getProviderName')->willReturn('netbull_media.provider.image');

        $provider = $this->createMock(MediaProviderInterface::class);
        $provider->expects(self::once())->method('generateThumbnail')->with($media, 'normal');

        $pool = $this->createMock(Pool::class);
        $pool->method('getProvider')->with('netbull_media.provider.image')->willReturn($provider);

        $handler = new GenerateThumbnailMessageHandler($this->em($media, 7), $pool);
        $handler(new GenerateThumbnailMessage(7, 'normal'));
    }

    public function testDoesNothingWhenMediaIsMissing(): void
    {
        // No media -> the provider is never resolved and nothing is generated (no exception).
        $pool = $this->createMock(Pool::class);
        $pool->expects(self::never())->method('getProvider');

        $handler = new GenerateThumbnailMessageHandler($this->em(null, 99), $pool);
        $handler(new GenerateThumbnailMessage(99, 'normal'));
    }

    private function em(?MediaInterface $media, int $expectedId): EntityManagerInterface
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('find')->with($expectedId)->willReturn($media);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(Media::class)->willReturn($repository);

        return $em;
    }
}
