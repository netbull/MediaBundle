<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Message;

/**
 * Dispatched (one per format) when thumbnails should be generated asynchronously.
 *
 * Enabled via `netbull_media.thumbnail.async: true`. Route this message to an async transport
 * in your application's messenger configuration to offload image resizing to a worker; if no
 * transport is configured Messenger handles it synchronously in the same process.
 */
final class GenerateThumbnailMessage
{
    public function __construct(
        private readonly int $mediaId,
        private readonly string $format,
    ) {
    }

    public function getMediaId(): int
    {
        return $this->mediaId;
    }

    public function getFormat(): string
    {
        return $this->format;
    }
}
