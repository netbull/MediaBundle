<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Tests\Provider;

use Gaufrette\File;
use Gaufrette\Filesystem;
use NetBull\MediaBundle\Cdn\CdnInterface;
use NetBull\MediaBundle\Entity\Media;
use NetBull\MediaBundle\Provider\FileProvider;
use NetBull\MediaBundle\Signature\SimpleSignatureHasher;
use NetBull\MediaBundle\Thumbnail\ThumbnailInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\Routing\RouterInterface;

/**
 * Re-storing a media's binary must delete the PREVIOUS original from the media's own storage
 * directory. The previous implementation looked it up through a clone of the media, and
 * Media::__clone() drops the id, so the lookup landed in the directory of id 0 and never found
 * the file: every re-upload leaked one stale original (245k objects / 35 GB in one context by
 * October 2026).
 */
#[CoversClass(FileProvider::class)]
final class FileProviderPostUpdateTest extends TestCase
{
    /** @var list<string> keys passed to has() */
    private array $checked = [];

    /** @var list<string> keys passed to delete() */
    private array $deleted = [];

    /** @var list<string> keys passed to get() for writing */
    private array $written = [];

    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
    }

    public function testPostUpdateDeletesThePreviousOriginalFromTheMediaOwnDirectory(): void
    {
        $media = $this->storedMedia(4653507, 'old.jpg'); // id 4653507 lives in connecthub_hotel/0047/54
        $media->setBinaryContent($this->newBinary());    // remembers old.jpg as the previous reference
        $media->setProviderReference('new.jpg');         // what transform() assigns before the UPDATE

        $this->provider()->postUpdate($media);

        self::assertSame(['connecthub_hotel/0047/54/old.jpg'], $this->checked, 'the previous original is looked up in the media directory, not in the directory of id 0');
        self::assertSame(['connecthub_hotel/0047/54/old.jpg'], $this->deleted);
        self::assertSame(['connecthub_hotel/0047/54/new.jpg'], $this->written);
        self::assertNull($media->getBinaryContent(), 'binary content is reset after storing');
    }

    public function testPostUpdateWithoutAPreviousReferenceDeletesNothing(): void
    {
        $media = $this->storedMedia(4653507, null); // never stored before: nothing to delete
        $media->setBinaryContent($this->newBinary());
        $media->setProviderReference('new.jpg');

        $this->provider()->postUpdate($media);

        self::assertSame([], $this->checked);
        self::assertSame([], $this->deleted);
        self::assertSame(['connecthub_hotel/0047/54/new.jpg'], $this->written);
    }

    public function testPostUpdateWithoutANewBinaryTouchesNothing(): void
    {
        $media = $this->storedMedia(4653507, 'old.jpg'); // a metadata-only update

        $this->provider()->postUpdate($media);

        self::assertSame([], $this->checked);
        self::assertSame([], $this->deleted);
        self::assertSame([], $this->written);
    }

    private function storedMedia(int $id, ?string $reference): Media
    {
        $media = new Media();
        (new ReflectionProperty(Media::class, 'id'))->setValue($media, $id);
        $media->setContext('connecthub_hotel')->setProviderReference($reference);

        return $media;
    }

    private function newBinary(): SymfonyFile
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'nbm_');
        file_put_contents($path, 'new bytes');
        $this->tempFiles[] = $path;

        return new SymfonyFile($path);
    }

    private function provider(): FileProvider
    {
        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('has')->willReturnCallback(function (string $key): bool {
            $this->checked[] = $key;

            return true;
        });
        $filesystem->method('delete')->willReturnCallback(function (string $key): bool {
            $this->deleted[] = $key;

            return true;
        });
        $filesystem->method('get')->willReturnCallback(function (string $key, bool $create = false): File {
            $this->written[] = $key;

            return $this->createStub(File::class); // setContent() on the stub is a no-op
        });

        return new FileProvider(
            'netbull_media.provider.file',
            $filesystem,
            $this->createStub(CdnInterface::class),
            $this->createStub(ThumbnailInterface::class),
            $this->createStub(RouterInterface::class),
            new SimpleSignatureHasher('secret'),
        );
    }
}
