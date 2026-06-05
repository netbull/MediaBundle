<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Tests\Thumbnail;

use Gaufrette\File;
use Gaufrette\FilesystemInterface;
use LogicException;
use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Message\GenerateThumbnailMessage;
use NetBull\MediaBundle\Provider\MediaProviderInterface;
use NetBull\MediaBundle\Resizer\ResizerInterface;
use NetBull\MediaBundle\Thumbnail\FormatThumbnail;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[CoversClass(FormatThumbnail::class)]
class FormatThumbnailTest extends TestCase
{
    public function testSyncGenerationWorksWithoutAMessageBus(): void
    {
        // symfony/messenger is optional: in the default sync mode the thumbnailer is built with a
        // null bus and must not require Messenger. A provider that needs no thumbnails returns early.
        $provider = $this->createMock(MediaProviderInterface::class);
        $provider->method('requireThumbnails')->willReturn(false);

        $thumbnail = new FormatThumbnail(new NullLogger(), null, false);
        $thumbnail->generate($provider, $this->createMock(MediaInterface::class));

        $this->expectNotToPerformAssertions();
    }

    public function testSyncGenerationResizesEachMatchingFormatWithoutABus(): void
    {
        // Full sync path (requireThumbnails() == true) must resize in-process and never touch the
        // message bus, so a null bus (symfony/messenger absent) works end to end.
        $media = $this->createMock(MediaInterface::class);
        $media->method('getContext')->willReturn('default');
        $media->method('getExtension')->willReturn('jpg');

        $referenceFile = $this->createMock(File::class);
        $referenceFile->method('exists')->willReturn(true);

        $outFile = $this->createMock(File::class);

        $filesystem = $this->createMock(FilesystemInterface::class);
        $filesystem->method('get')->with('private/default_thumb', true)->willReturn($outFile);

        // Only the format matching the media context ('default_thumb') is resized; 'avatar_small' is skipped.
        $resizer = $this->createMock(ResizerInterface::class);
        $resizer->expects(self::once())
            ->method('resize')
            ->with($media, $referenceFile, $outFile, 'jpg', ['width' => 150]);

        $provider = $this->createMock(MediaProviderInterface::class);
        $provider->method('requireThumbnails')->willReturn(true);
        $provider->method('getReferenceFile')->with($media)->willReturn($referenceFile);
        $provider->method('getFormats')->willReturn([
            'default_thumb' => ['width' => 150],
            'avatar_small' => ['width' => 32],
        ]);
        $provider->method('generatePrivateUrl')->with($media, 'default_thumb')->willReturn('private/default_thumb');
        $provider->method('getFilesystem')->willReturn($filesystem);
        $provider->method('getResizer')->willReturn($resizer);

        $thumbnail = new FormatThumbnail(new NullLogger(), null, false);
        $thumbnail->generate($provider, $media);
    }

    public function testAsyncGenerationThrowsWhenNoMessageBusIsAvailable(): void
    {
        // Async requested but Messenger is not installed (null bus) -> fail with a clear message
        // instead of a confusing "call dispatch() on null" error.
        $provider = $this->createMock(MediaProviderInterface::class);
        $provider->method('requireThumbnails')->willReturn(true);

        $thumbnail = new FormatThumbnail(new NullLogger(), null, true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('symfony/messenger');

        $thumbnail->generate($provider, $this->createMock(MediaInterface::class));
    }

    public function testAsyncGenerationDispatchesOneMessagePerMatchingFormat(): void
    {
        $media = $this->createMock(MediaInterface::class);
        $media->method('getId')->willReturn(7);
        $media->method('getContext')->willReturn('default');

        $provider = $this->createMock(MediaProviderInterface::class);
        $provider->method('requireThumbnails')->willReturn(true);
        // Only formats prefixed with the media context are dispatched; the context prefix is stripped.
        $provider->method('getFormats')->willReturn([
            'default_thumb' => ['width' => 150],
            'default_normal' => ['width' => 600],
            'avatar_small' => ['width' => 32],
        ]);

        $dispatched = [];
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturnCallback(static function (object $message) use (&$dispatched): Envelope {
            $dispatched[] = $message;

            return new Envelope($message);
        });

        $thumbnail = new FormatThumbnail(new NullLogger(), $bus, true);
        $thumbnail->generate($provider, $media);

        self::assertCount(2, $dispatched);
        self::assertContainsOnlyInstancesOf(GenerateThumbnailMessage::class, $dispatched);
        self::assertSame(['thumb', 'normal'], array_map(static fn (GenerateThumbnailMessage $m) => $m->getFormat(), $dispatched));
        self::assertSame([7, 7], array_map(static fn (GenerateThumbnailMessage $m) => $m->getMediaId(), $dispatched));
    }
}
