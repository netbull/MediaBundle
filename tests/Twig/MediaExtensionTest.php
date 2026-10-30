<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Tests\Twig;

use Doctrine\ORM\EntityManagerInterface;
use NetBull\MediaBundle\Provider\Pool;
use NetBull\MediaBundle\Twig\MediaExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * Templates rendered without strict_variables hand the media filters null for a missing photo
 * (`gallery.0|path` on an empty gallery, `item.photos.main|path` when there is none). Up to 5.x
 * the untyped `$media` parameter rendered an empty string for it; the typed 7.x signatures turned
 * that into a TypeError that failed the whole page.
 */
#[CoversClass(MediaExtension::class)]
final class MediaExtensionTest extends TestCase
{
    private MediaExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new MediaExtension(new Pool('default'), $this->createStub(EntityManagerInterface::class));
    }

    public function testPathRendersEmptyStringForNullMedia(): void
    {
        self::assertSame('', $this->extension->generatePublicPath(null, 'big'));
    }

    public function testSecurePathRendersEmptyStringForNullMedia(): void
    {
        self::assertSame('', $this->extension->generateSecurePath(null, 'identifier'));
    }

    public function testThumbnailAndViewRenderEmptyStringForNullMedia(): void
    {
        $environment = new Environment(new ArrayLoader());

        self::assertSame('', $this->extension->generateThumbnail($environment, null, 'normal'));
        self::assertSame('', $this->extension->generateView($environment, null, 'normal'));
    }

    public function testPathRendersEmptyStringForArrayWithoutProviderName(): void
    {
        self::assertSame('', $this->extension->generatePublicPath([], 'big'));
    }

    public function testPathFilterAcceptsNullFromTwig(): void
    {
        $twig = new Environment(new ArrayLoader(['gallery' => '[{{ gallery.0|path("bigger") }}]']));
        $twig->addExtension($this->extension);

        self::assertSame('[]', $twig->render('gallery', ['gallery' => []]));
        self::assertSame('[]', $twig->render('gallery', ['gallery' => null]));
    }
}
