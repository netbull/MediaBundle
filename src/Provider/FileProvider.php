<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Provider;

use Gaufrette\File;
use Gaufrette\Filesystem;
use NetBull\MediaBundle\Cdn\CdnInterface;
use NetBull\MediaBundle\Entity\MediaInterface;
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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class FileProvider extends BaseProvider
{
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

        // this is the name used to store the file
        if (!$media->getProviderReference()) {
            $media->setProviderReference($this->generateReferenceName($media));
        }

        if ($media->getBinaryContent()) {
            $media->setContentType($media->getBinaryContent()->getMimeType());
            $media->setSize($media->getBinaryContent()->getSize());
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

        $hash = $this->simpleSignatureHasher->computeSignatureHash($identifier, $time);
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
        // build the default headers
        $headers = array_merge([
            'Content-Type' => $media->getContentType(),
            'Content-Disposition' => \sprintf('attachment; filename="%s"', $media->getMetadataValue('filename')),
        ], $headers);

        if (!\in_array($mode, ['http', 'X-Sendfile', 'X-Accel-Redirect'], true)) {
            throw new RuntimeException('Invalid mode provided');
        }

        if ('reference' === $format) {
            $file = $this->getReferenceFile($media);
        } else {
            $file = $this->getFilesystem()->get($this->generatePrivateUrl($media, $format));
        }

        return new StreamedResponse(static function () use ($file) {
            echo $file->getContent();
        }, 200, $headers);
    }

    public function getViewResponse(MediaInterface $media, string $format, array $headers = []): Response
    {
        // build the default headers
        $headers = array_merge([
            'Content-Type' => $media->getContentType(),
            'Content-Disposition' => \sprintf('inline; filename="%s"', $media->getMetadataValue('filename')),
        ], $headers);

        if ('reference' === $format) {
            $file = $this->getReferenceFile($media);
        } else {
            $file = $this->getFilesystem()->get($this->generatePrivateUrl($media, $format));
        }

        return new StreamedResponse(static function () use ($file) {
            echo $file->getContent();
        }, 200, $headers);
    }
}
