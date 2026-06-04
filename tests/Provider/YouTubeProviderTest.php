<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Tests\Provider;

use Gaufrette\Filesystem;
use NetBull\MediaBundle\Cdn\CdnInterface;
use NetBull\MediaBundle\Entity\Media;
use NetBull\MediaBundle\Provider\YouTubeProvider;
use NetBull\MediaBundle\Thumbnail\ThumbnailInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Stringable;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(YouTubeProvider::class)]
class YouTubeProviderTest extends TestCase
{
    public function testUpdateMetadataPopulatesFieldsFromOembed(): void
    {
        $body = json_encode(['title' => 'My Video', 'width' => 640, 'height' => 480], \JSON_THROW_ON_ERROR);
        $provider = $this->provider(new MockHttpClient(new MockResponse($body)));

        $media = (new Media())->setProviderReference('dQw4w9WgXcQ');
        $provider->updateMetadata($media, true);

        self::assertSame('My Video', $media->getName());
        self::assertSame(640, $media->getWidth());
        self::assertSame(480, $media->getHeight());
        self::assertSame('video/x-flv', $media->getContentType());
    }

    public function testUpdateMetadataDisablesMediaAndLogsOnFailure(): void
    {
        $logger = $this->recordingLogger();
        $provider = $this->provider(new MockHttpClient(new MockResponse('', ['http_code' => 500])), $logger);

        $media = (new Media())->setProviderReference('dQw4w9WgXcQ');
        $media->setEnabled(true);

        $provider->updateMetadata($media, true);

        self::assertFalse($media->isEnabled(), 'media should be disabled when its metadata cannot be fetched');
        $warnings = array_filter($logger->records, static fn (array $record): bool => 'warning' === $record[0]);
        self::assertNotEmpty($warnings, 'a warning should be logged on metadata failure');
    }

    private function provider(MockHttpClient $client, ?AbstractLogger $logger = null): YouTubeProvider
    {
        $provider = new YouTubeProvider(
            'netbull_media.provider.youtube',
            $this->createMock(Filesystem::class),
            $this->createMock(CdnInterface::class),
            $this->createMock(ThumbnailInterface::class),
            null,
            true,
        );
        $provider->setHttpClient($client);
        if (null !== $logger) {
            $provider->setLogger($logger);
        }

        return $provider;
    }

    /**
     * @return AbstractLogger&object{records: array<int, array{0: mixed, 1: string}>}
     */
    private function recordingLogger(): AbstractLogger
    {
        return new class extends AbstractLogger {
            /** @var array<int, array{0: mixed, 1: string}> */
            public array $records = [];

            public function log($level, string|Stringable $message, array $context = []): void
            {
                $this->records[] = [$level, (string) $message];
            }
        };
    }
}
