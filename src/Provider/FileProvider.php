<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Provider;

use Gaufrette\Exception\FileNotFound;
use Gaufrette\File;
use Gaufrette\Filesystem;
use Gaufrette\StreamMode;
use NetBull\MediaBundle\Cdn\CdnInterface;
use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Filesystem\S3Gateway;
use NetBull\MediaBundle\Metadata\MetadataBuilderInterface;
use NetBull\MediaBundle\Signature\SimpleSignatureHasher;
use NetBull\MediaBundle\Thumbnail\ThumbnailInterface;
use RuntimeException;
use SplFileInfo;
use SplFileObject;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class FileProvider extends BaseProvider
{
    protected ?S3Gateway $gateway = null;

    public function __construct(
        string $name,
        Filesystem $filesystem,
        CdnInterface $cdn,
        ThumbnailInterface $thumbnail,
        protected RouterInterface $router,
        protected SimpleSignatureHasher $simpleSignatureHasher,
        protected array $allowedExtensions = [],
        protected array $allowedMimeTypes = [],
        protected ?MetadataBuilderInterface $metadata = null,
    ) {
        parent::__construct($name, $filesystem, $cdn, $thumbnail);
    }

    /**
     * Injected by the bundle for S3-backed providers. Secured downloads are then proxy-streamed
     * through PHP (default) or, when the context's download/view mode is "redirect", served by
     * redirecting to a short-lived pre-signed URL. Null for local storage (which streams via Gaufrette).
     */
    public function setGateway(?S3Gateway $gateway): void
    {
        $this->gateway = $gateway;
    }

    public function getReferenceImage(array|MediaInterface $media): string
    {
        return \sprintf('%s/%s',
            $this->generatePath($media),
            $media instanceof MediaInterface ? $media->getProviderReference() : $media['providerReference'],
        );
    }

    public function getReferenceFile(array|MediaInterface $media): ?File
    {
        return $this->getFilesystem()->get($this->getReferenceImage($media), true);
    }

    public function buildMediaType(FormBuilderInterface $formBuilder, array $options = []): void
    {
        if (isset($options['main_field'])) {
            unset($options['main_field']);
        }
        $formBuilder->add('binaryContent', FileType::class, $options);
    }

    public function buildShortMediaType(FormBuilderInterface $formBuilder, array $options = []): void
    {
        $formBuilder
            ->add('newBinaryContent', FileType::class, array_merge([
                'attr' => [
                    'class' => 'image-upload',
                ],
            ], $options))
        ;
    }

    public function postPersist(MediaInterface $media): void
    {
        if (null === $media->getBinaryContent()) {
            return;
        }

        $this->setFileContents($media);
        $media->resetBinaryContent();
    }

    public function postUpdate(MediaInterface $media): void
    {
        if (!$media->getBinaryContent() instanceof SplFileInfo) {
            return;
        }

        // Delete the current file from the FS
        $oldMedia = clone $media;

        // if no previous reference is provided, it prevents
        // Filesystem from trying to remove a directory
        if (null !== $media->getPreviousProviderReference()) {
            $oldMedia->setProviderReference($media->getPreviousProviderReference());
            $path = $this->getReferenceImage($oldMedia);
            if ($this->getFilesystem()->has($path)) {
                $this->getFilesystem()->delete($path);
            }
        }

        $this->fixBinaryContent($media);
        $this->setFileContents($media);
        $media->resetBinaryContent();
    }

    public function postFlush(MediaInterface $media): void
    {
        $this->generateThumbnails($media);
    }

    /**
     * @throws RuntimeException
     */
    protected function fixBinaryContent(MediaInterface $media): void
    {
        if (null === $media->getBinaryContent()) {
            return;
        }

        // if the binary content is a filename => convert to a valid File
        if (!$media->getBinaryContent() instanceof SymfonyFile) {
            if (!is_file($media->getBinaryContent())) {
                throw new RuntimeException('The file does not exist : ' . $media->getBinaryContent());
            }

            $binaryContent = new SymfonyFile($media->getBinaryContent());

            $media->setBinaryContent($binaryContent);
        }
    }

    /**
     * @throws RuntimeException
     */
    protected function fixFilename(MediaInterface $media): void
    {
        if ($media->getBinaryContent() instanceof UploadedFile) {
            $media->setName($media->getName() ?: $media->getBinaryContent()->getClientOriginalName());
            $media->setMetadataValue('filename', $media->getBinaryContent()->getClientOriginalName());
        } elseif ($media->getBinaryContent() instanceof File || $media->getBinaryContent() instanceof SymfonyFile) {
            $media->setName($media->getName() ?: $media->getBinaryContent()->getBasename());
            $media->setMetadataValue('filename', $media->getBinaryContent()->getBasename());
        }

        // this is the original name
        if (!$media->getName()) {
            throw new RuntimeException('Please define a valid media\'s name');
        }
    }

    protected function doTransform(MediaInterface $media): void
    {
        $this->fixBinaryContent($media);
        $this->fixFilename($media);
        $this->validateBinaryContent($media);

        // this is the name used to store the file
        if (!$media->getProviderReference()) {
            $media->setProviderReference($this->generateReferenceName($media));
        }

        if ($media->getBinaryContent()) {
            $media->setContentType($media->getBinaryContent()->getMimeType());
            $media->setSize($media->getBinaryContent()->getSize());
        }
    }

    /**
     * Enforce the configured allowed_extensions / allowed_mime_types on the uploaded file.
     *
     * The mime type and extension are derived from the file's content (File::getMimeType()
     * / File::guessExtension() sniff the file, they do NOT trust the client-supplied
     * Content-Type or filename), so this is a genuine upload restriction. A file is accepted
     * only when it positively matches at least one configured allow-list; anything that
     * matches neither (e.g. .php, .svg, .html disguised as an image, executables) is rejected.
     *
     * @throws RuntimeException when the file matches none of the configured allow-lists
     */
    protected function validateBinaryContent(MediaInterface $media): void
    {
        $binaryContent = $media->getBinaryContent();
        if (!$binaryContent instanceof SymfonyFile) {
            return;
        }

        // Nothing configured to enforce.
        if ([] === $this->allowedMimeTypes && [] === $this->allowedExtensions) {
            return;
        }

        $mimeType = $binaryContent->getMimeType();
        $extension = $binaryContent->guessExtension();

        $mimeAllowed = [] !== $this->allowedMimeTypes
            && null !== $mimeType
            && \in_array($mimeType, $this->allowedMimeTypes, true);

        $extensionAllowed = [] !== $this->allowedExtensions
            && null !== $extension
            && \in_array(strtolower($extension), array_map('strtolower', $this->allowedExtensions), true);

        if (!$mimeAllowed && !$extensionAllowed) {
            throw new RuntimeException(\sprintf(
                'The uploaded file is not allowed (detected mime type "%s", extension "%s").',
                $mimeType ?? 'unknown',
                $extension ?? 'unknown',
            ));
        }
    }

    public function updateMetadata(MediaInterface $media, bool $force = true): void
    {
        // this is now optimized at all!!!
        $path = tempnam(sys_get_temp_dir(), 'update_metadata');
        $fileObject = new SplFileObject($path, 'w');
        $fileObject->fwrite($this->getReferenceFile($media)->getContent());

        $media->setSize($fileObject->getSize());
    }

    public function generatePublicUrl(array|MediaInterface $media, string $format): string
    {
        if ('reference' === $format) {
            $path = $this->getReferenceImage($media);
        } else {
            $path = \sprintf('../files/%s/file.png', $format);
        }

        return $this->getCdn()->getPath($path);
    }

    /**
     * Generate the secured url.
     */
    public function generateSecuredUrl(array|MediaInterface $media, string $format, string $identifier, int $expires = 300): string
    {
        $id = $media instanceof MediaInterface ? $media->getId() : $media['id'];

        $time = time() + $expires;

        $hash = $this->simpleSignatureHasher->computeSignatureHash($identifier, $time, (string) $id);
        $params = [
            'id' => $id,
            'format' => $format,
            'u' => $identifier,
            'e' => $time,
            'h' => $hash,
        ];

        return $this->router->generate('netbull_media_view', $params, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function getHelperProperties(array|MediaInterface $media, string $format, array $options = []): array
    {
        if ($media instanceof MediaInterface) {
            $data = [
                'title' => $media->getName(),
                'thumbnail' => $this->getReferenceImage($media),
                'file' => $this->getReferenceImage($media),
            ];
        } else {
            $data = [
                'title' => $media['name'],
                'thumbnail' => $this->getReferenceImage($media),
                'file' => $this->getReferenceImage($media),
            ];
        }

        return array_merge($data, $options);
    }

    public function generatePrivateUrl(MediaInterface $media, string $format): string
    {
        if ('reference' === $format) {
            return $this->getReferenceImage($media);
        }

        return '';
    }

    /**
     * Set the file contents for an image.
     *
     * @param string|null $contents path to contents, defaults to MediaInterface BinaryContent
     */
    protected function setFileContents(MediaInterface $media, ?string $contents = null): void
    {
        $file = $this->getFilesystem()->get(\sprintf('%s/%s', $this->generatePath($media), $media->getProviderReference()), true);

        if (!$contents) {
            $contents = $media->getBinaryContent()->getRealPath();
        }

        $metadata = $this->metadata ? $this->metadata->get($file->getName()) : [];
        $file->setContent(file_get_contents($contents), $metadata);
    }

    protected function generateReferenceName(MediaInterface $media): string
    {
        return $this->generateMediaUniqId($media) . '.' . $media->getBinaryContent()->guessExtension();
    }

    protected function generateMediaUniqId(MediaInterface $media): string
    {
        return sha1($media->getName() . uniqid() . rand(11111, 99999));
    }

    public function getDownloadResponse(MediaInterface $media, string $format, string $mode, array $headers = []): Response
    {
        if (!\in_array($mode, ['http', 'X-Sendfile', 'X-Accel-Redirect', 'stream', 'redirect'], true)) {
            throw new RuntimeException('Invalid mode provided');
        }

        // build the default headers
        $headers = array_merge([
            'Content-Type' => $media->getContentType(),
            'Content-Disposition' => \sprintf('attachment; filename="%s"', $media->getMetadataValue('filename')),
        ], $headers);

        return $this->buildFileResponse($this->resolveStorageKey($media, $format), $headers, $mode);
    }

    public function getViewResponse(MediaInterface $media, string $format, string $mode = 'stream', array $headers = []): Response
    {
        // build the default headers
        $headers = array_merge([
            'Content-Type' => $media->getContentType(),
            'Content-Disposition' => \sprintf('inline; filename="%s"', $media->getMetadataValue('filename')),
        ], $headers);

        return $this->buildFileResponse($this->resolveStorageKey($media, $format), $headers, $mode);
    }

    /**
     * S3-backed providers proxy-stream the file through PHP by default (a same-origin 200 an SPA can
     * fetch over XHR); with mode "redirect" they 302 to a short-lived pre-signed URL instead (bytes
     * stream S3 -> client, never through PHP — best for large files). Local storage always streams
     * in chunks.
     */
    private function buildFileResponse(string $key, array $headers, string $mode = 'stream'): Response
    {
        if (null !== $this->gateway) {
            if ('redirect' === $mode) {
                $overrides = [];
                if (isset($headers['Content-Type'])) {
                    $overrides['ResponseContentType'] = $headers['Content-Type'];
                }
                if (isset($headers['Content-Disposition'])) {
                    $overrides['ResponseContentDisposition'] = $headers['Content-Disposition'];
                }

                return new RedirectResponse($this->gateway->createPresignedUrl($key, 300, $overrides), 302);
            }

            return $this->streamS3Response($key, $headers);
        }

        return $this->streamResponse($key, $headers);
    }

    /**
     * Proxy-stream an S3 object through PHP in fixed-size chunks pulled lazily from the live S3
     * response (constant memory), so the endpoint returns a same-origin 200 the SPA can consume over
     * XHR — without the Gaufrette AwsS3 adapter buffering the whole object into memory.
     */
    private function streamS3Response(string $key, array $headers): StreamedResponse
    {
        $object = $this->gateway->openObjectStream($key);
        $stream = $object['stream'];

        if (null !== $object['length'] && !isset($headers['Content-Length'])) {
            $headers['Content-Length'] = (string) $object['length'];
        }

        return new StreamedResponse(static function () use ($stream): void {
            while (!$stream->eof()) {
                echo $stream->read(8192);
                flush();
            }

            $stream->close();
        }, Response::HTTP_OK, $headers);
    }

    /**
     * Resolve the filesystem key for the requested format and ensure it exists, so a missing file
     * surfaces as a FileNotFound (-> 404) before the streamed response starts and headers are sent.
     *
     * @throws FileNotFound
     */
    private function resolveStorageKey(MediaInterface $media, string $format): string
    {
        $key = 'reference' === $format
            ? $this->getReferenceImage($media)
            : $this->generatePrivateUrl($media, $format);

        if (!$this->getFilesystem()->has($key)) {
            throw new FileNotFound($key);
        }

        return $key;
    }

    /**
     * Stream the file in fixed-size chunks instead of buffering the whole payload into a PHP string,
     * which would otherwise hold the entire file in memory (and OOM the worker on large files).
     *
     * NOTE: the Gaufrette 0.9 AwsS3 adapter does not implement StreamFactory, so for S3-backed
     * storage Gaufrette still buffers the object in memory. Large private files on S3 are better
     * served through the CDN or a pre-signed URL than proxied through PHP.
     */
    private function streamResponse(string $key, array $headers): StreamedResponse
    {
        $filesystem = $this->getFilesystem();

        return new StreamedResponse(static function () use ($filesystem, $key): void {
            $stream = $filesystem->createStream($key);
            $stream->open(new StreamMode('rb'));

            while (!$stream->eof()) {
                echo $stream->read(8192);
                flush();
            }

            $stream->close();
        }, Response::HTTP_OK, $headers);
    }
}
