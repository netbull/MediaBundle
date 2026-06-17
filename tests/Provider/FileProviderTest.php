<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Tests\Provider;

use Gaufrette\Filesystem;
use NetBull\MediaBundle\Cdn\CdnInterface;
use NetBull\MediaBundle\Entity\Media;
use NetBull\MediaBundle\Filesystem\S3Gateway;
use NetBull\MediaBundle\Provider\FileProvider;
use NetBull\MediaBundle\Signature\SimpleSignatureHasher;
use NetBull\MediaBundle\Thumbnail\ThumbnailInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\RouterInterface;

#[CoversClass(FileProvider::class)]
class FileProviderTest extends TestCase
{
    public function testSecuredDownloadRedirectsToPresignedUrlInRedirectMode(): void
    {
        $gateway = $this->createMock(S3Gateway::class);
        $gateway->method('createPresignedUrl')->willReturn('https://s3.example/signed');

        $provider = $this->provider();
        $provider->setGateway($gateway);

        $response = $provider->getDownloadResponse($this->media(), 'reference', 'redirect');

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('https://s3.example/signed', $response->getTargetUrl());
    }

    public function testSecuredDownloadStreamsThroughGatewayByDefaultWhenS3Backed(): void
    {
        $gateway = $this->createMock(S3Gateway::class);
        $gateway->method('openObjectStream')->willReturn([
            'stream' => $this->createMock(StreamInterface::class),
            'length' => 10,
        ]);

        $provider = $this->provider();
        $provider->setGateway($gateway);

        $response = $provider->getDownloadResponse($this->media(), 'reference', 'stream');

        self::assertInstanceOf(StreamedResponse::class, $response);
    }

    public function testSecuredDownloadStreamsWhenNoGateway(): void
    {
        $response = $this->provider()->getDownloadResponse($this->media(), 'reference', 'stream');

        self::assertInstanceOf(StreamedResponse::class, $response);
    }

    private function provider(): FileProvider
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('has')->willReturn(true);

        return new FileProvider(
            'netbull_media.provider.file',
            $filesystem,
            $this->createMock(CdnInterface::class),
            $this->createMock(ThumbnailInterface::class),
            $this->createMock(RouterInterface::class),
            new SimpleSignatureHasher('secret'),
        );
    }

    private function media(): Media
    {
        return (new Media())->setContext('default')->setProviderReference('file.jpg');
    }
}
