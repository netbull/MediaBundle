<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Tests\Filesystem;

use Aws\CommandInterface;
use Aws\S3\S3ClientInterface;
use NetBull\MediaBundle\Filesystem\S3Presigner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

#[CoversClass(S3Presigner::class)]
class S3PresignerTest extends TestCase
{
    public function testBuildsPresignedUrlWithDirectoryPrefixAndOverrides(): void
    {
        $command = $this->createMock(CommandInterface::class);

        $client = $this->createMock(S3ClientInterface::class);
        $client->expects(self::once())
            ->method('getCommand')
            ->with('GetObject', [
                'Bucket' => 'my-bucket',
                'Key' => 'media/default/0001/01/file.jpg',
                'ResponseContentDisposition' => 'attachment; filename="file.jpg"',
            ])
            ->willReturn($command);
        $client->expects(self::once())
            ->method('createPresignedRequest')
            ->with($command, '+300 seconds')
            ->willReturn($this->request('https://s3.example/signed?sig=1'));

        $presigner = new S3Presigner($client, 'my-bucket', 'media');

        $url = $presigner->createPresignedUrl('default/0001/01/file.jpg', 300, [
            'ResponseContentDisposition' => 'attachment; filename="file.jpg"',
        ]);

        self::assertSame('https://s3.example/signed?sig=1', $url);
    }

    public function testBuildsKeyWithoutPrefixWhenNoDirectoryConfigured(): void
    {
        $client = $this->createMock(S3ClientInterface::class);
        $client->expects(self::once())
            ->method('getCommand')
            ->with('GetObject', ['Bucket' => 'b', 'Key' => 'a/b.png'])
            ->willReturn($this->createMock(CommandInterface::class));
        $client->method('createPresignedRequest')->willReturn($this->request('https://x/y'));

        $presigner = new S3Presigner($client, 'b');

        self::assertSame('https://x/y', $presigner->createPresignedUrl('a/b.png'));
    }

    private function request(string $uri): RequestInterface
    {
        $uriObject = $this->createMock(UriInterface::class);
        $uriObject->method('__toString')->willReturn($uri);

        $request = $this->createMock(RequestInterface::class);
        $request->method('getUri')->willReturn($uriObject);

        return $request;
    }
}
