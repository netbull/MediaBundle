<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Filesystem;

use Aws\S3\S3ClientInterface;

/**
 * Builds short-lived pre-signed S3 GET URLs so secured downloads can be served by redirecting the
 * client straight to S3 — the bytes never transit PHP (constant memory, no proxy bandwidth).
 *
 * The object key is computed the same way the Gaufrette AwsS3 adapter does (optional directory
 * prefix), so the URL points at the exact stored object.
 */
class S3Presigner
{
    public function __construct(
        private readonly S3ClientInterface $client,
        private readonly string $bucket,
        private readonly string $directory = '',
    ) {
    }

    /**
     * @param array<string, string> $responseOverrides response header overrides baked into the
     *                                                 signed URL (e.g. ResponseContentDisposition,
     *                                                 ResponseContentType)
     */
    public function createPresignedUrl(string $key, int $expires = 300, array $responseOverrides = []): string
    {
        $objectKey = '' === $this->directory ? $key : \sprintf('%s/%s', $this->directory, $key);

        $command = $this->client->getCommand('GetObject', array_merge([
            'Bucket' => $this->bucket,
            'Key' => $objectKey,
        ], $responseOverrides));

        $request = $this->client->createPresignedRequest($command, \sprintf('+%d seconds', $expires));

        return (string) $request->getUri();
    }
}
