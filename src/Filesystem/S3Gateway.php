<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Filesystem;

use Aws\S3\S3ClientInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Thin gateway over the S3 client for serving secured downloads. It can either sign a short-lived
 * GET URL (redirect the client straight to S3 — bytes never transit PHP) or open a lazy read stream
 * so the bytes can be proxied through PHP for SPA clients that fetch the endpoint over XHR and cannot
 * follow a cross-origin redirect to a pre-signed URL.
 *
 * The object key is computed the same way the Gaufrette AwsS3 adapter does (optional directory
 * prefix), so both paths point at the exact stored object.
 */
class S3Gateway
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
        $command = $this->client->getCommand('GetObject', array_merge([
            'Bucket' => $this->bucket,
            'Key' => $this->objectKey($key),
        ], $responseOverrides));

        $request = $this->client->createPresignedRequest($command, \sprintf('+%d seconds', $expires));

        return (string) $request->getUri();
    }

    /**
     * Open a lazy, constant-memory read stream straight from S3: the bytes are pulled from the live
     * HTTP response as they are read, never buffered whole into PHP (unlike the Gaufrette AwsS3
     * adapter, which has no StreamFactory and loads the entire object into memory).
     *
     * @return array{stream: StreamInterface, length: int|null}
     */
    public function openObjectStream(string $key): array
    {
        $result = $this->client->getObject([
            'Bucket' => $this->bucket,
            'Key' => $this->objectKey($key),
            '@http' => ['stream' => true],
        ]);

        $length = $result['ContentLength'] ?? null;

        return [
            'stream' => $result['Body'],
            'length' => null === $length ? null : (int) $length,
        ];
    }

    private function objectKey(string $key): string
    {
        return '' === $this->directory ? $key : \sprintf('%s/%s', $this->directory, $key);
    }
}
