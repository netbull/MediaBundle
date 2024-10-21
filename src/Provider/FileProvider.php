<?php

namespace NetBull\MediaBundle\Provider;

use Gaufrette\Filesystem;
use NetBull\MediaBundle\Signature\SimpleSignatureHasher;
use RuntimeException;
use SplFileInfo;
use SplFileObject;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use NetBull\MediaBundle\Cdn\CdnInterface;
use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Thumbnail\ThumbnailInterface;
use NetBull\MediaBundle\Metadata\MetadataBuilderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Gaufrette\File;

class FileProvider extends BaseProvider
{
    /**
     * @var RouterInterface
     */
    protected RouterInterface $router;

    /**
     * @var SimpleSignatureHasher
     */
    protected SimpleSignatureHasher $simpleSignatureHasher;

    /**
     * @var array
     */
    protected array $allowedExtensions;

    /**
     * @var array
     */
    protected array $allowedMimeTypes;

    /**
     * @var MetadataBuilderInterface|null
     */
    protected ?MetadataBuilderInterface $metadata;

    /**
     * @param string $name
     * @param Filesystem $filesystem
     * @param CdnInterface $cdn
     * @param ThumbnailInterface $thumbnail
     * @param RouterInterface $router
     * @param SimpleSignatureHasher $simpleSignatureHasher
     * @param array $allowedExtensions
     * @param array $allowedMimeTypes
     * @param MetadataBuilderInterface|null $metadata
     */
    public function __construct(string $name, Filesystem $filesystem, CdnInterface $cdn, ThumbnailInterface $thumbnail, RouterInterface $router, SimpleSignatureHasher $simpleSignatureHasher, array $allowedExtensions = [], array $allowedMimeTypes = [], MetadataBuilderInterface $metadata = null)
    {
        parent::__construct($name, $filesystem, $cdn, $thumbnail);

        $this->router = $router;
        $this->simpleSignatureHasher = $simpleSignatureHasher;
        $this->allowedExtensions = $allowedExtensions;
        $this->allowedMimeTypes = $allowedMimeTypes;
        $this->metadata = $metadata;
    }

    /**
     * @param array|MediaInterface $media
     * @return string
     */
    public function getReferenceImage(array|MediaInterface $media): string
    {
        return sprintf('%s/%s',
            $this->generatePath($media),
            $media instanceof MediaInterface ? $media->getProviderReference() : $media['providerReference']
        );
    }

    /**
     * @param array|MediaInterface $media
     * @return File|null
     */
    public function getReferenceFile(array|MediaInterface $media): ?File
    {
        return $this->getFilesystem()->get($this->getReferenceImage($media), true);
    }

    /**
     * @param FormBuilderInterface $formBuilder
     * @param array $options
     * @return void
     */
    public function buildMediaType(FormBuilderInterface $formBuilder, array $options = []): void
    {
        if (isset($options['main_field'])) {
            unset($options['main_field']);
        }
        $formBuilder->add('binaryContent', FileType::class, $options);
    }

    /**
     * @param FormBuilderInterface $formBuilder
     * @param array $options
     */
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

    /**
     * @param MediaInterface $media
     * @return void
     */
    public function postPersist(MediaInterface $media): void
    {
        if ($media->getBinaryContent() === null) {
            return;
        }

        $this->setFileContents($media);
        $media->resetBinaryContent();
    }

    /**
     * @param MediaInterface $media
     * @return void
     */
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

    /**
     * @param MediaInterface $media
     * @return void
     */
    public function postFlush(MediaInterface $media): void
    {
        $this->generateThumbnails($media);
    }

    /**
     * @throws RuntimeException
     *
     * @param MediaInterface $media
     */
    protected function fixBinaryContent(MediaInterface $media): void
    {
        if ($media->getBinaryContent() === null) {
            return;
        }

        // if the binary content is a filename => convert to a valid File
        if (!$media->getBinaryContent() instanceof SymfonyFile) {
            if (!is_file($media->getBinaryContent())) {
                throw new RuntimeException('The file does not exist : '.$media->getBinaryContent());
            }

            $binaryContent = new SymfonyFile($media->getBinaryContent());

            $media->setBinaryContent($binaryContent);
        }
    }

    /**
     * @throws RuntimeException
     *
     * @param MediaInterface $media
     */
    protected function fixFilename(MediaInterface $media): void
    {
        if ($media->getBinaryContent() instanceof UploadedFile) {
            $media->setName($media->getName() ?: $media->getBinaryContent()->getClientOriginalName());
            $media->setMetadataValue('filename', $media->getBinaryContent()->getClientOriginalName());
        } elseif ($media->getBinaryContent() instanceof File) {
            $media->setName($media->getName() ?: $media->getBinaryContent()->getBasename());
            $media->setMetadataValue('filename', $media->getBinaryContent()->getBasename());
        }

        // this is the original name
        if (!$media->getName()) {
            throw new RuntimeException('Please define a valid media\'s name');
        }
    }

    /**
     * @param MediaInterface $media
     * @return void
     */
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

    /**
     * @param MediaInterface $media
     * @param bool $force
     * @return void
     */
    public function updateMetadata(MediaInterface $media, bool $force = true): void
    {
        // this is now optimized at all!!!
        $path = tempnam(sys_get_temp_dir(), 'update_metadata');
        $fileObject = new SplFileObject($path, 'w');
        $fileObject->fwrite($this->getReferenceFile($media)->getContent());

        $media->setSize($fileObject->getSize());
    }

    /**
     * @param array|MediaInterface $media
     * @param string $format
     * @return string
     */
    public function generatePublicUrl(array|MediaInterface $media, string $format): string
    {
        if ('reference' === $format) {
            $path = $this->getReferenceImage($media);
        } else {
            $path = sprintf('../files/%s/file.png', $format);
        }

        return $this->getCdn()->getPath($path);
    }

    /**
     * Generate the secured url.
     *
     * @param array|MediaInterface $media
     * @param string $format
     * @param string $identifier
     * @param int $expires
     *
     * @return string
     */
    public function generateSecuredUrl(array|MediaInterface $media, string $format, string $identifier, int $expires = 300): string
    {
        $id = $media instanceof MediaInterface ? $media->getId() : $media['id'];

        $time = time()+$expires;

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

    /**
     * @param array|MediaInterface $media
     * @param string $format
     * @param array $options
     * @return array
     */
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

    /**
     * @param MediaInterface $media
     * @param string $format
     * @return string
     */
    public function generatePrivateUrl(MediaInterface $media, string $format): string
    {
        if ('reference' === $format) {
            return $this->getReferenceImage($media);
        }

        return false;
    }

    /**
     * Set the file contents for an image.
     *
     * @param MediaInterface $media
     * @param string|null $contents path to contents, defaults to MediaInterface BinaryContent
     */
    protected function setFileContents(MediaInterface $media, string $contents = null): void
    {
        $file = $this->getFilesystem()->get(sprintf('%s/%s', $this->generatePath($media), $media->getProviderReference()), true);

        if (!$contents) {
            $contents = $media->getBinaryContent()->getRealPath();
        }

        $metadata = $this->metadata ? $this->metadata->get($file->getName()) : [];
        $file->setContent(file_get_contents($contents), $metadata);
    }

    /**
     * @param MediaInterface $media
     *
     * @return string
     */
    protected function generateReferenceName(MediaInterface $media): string
    {
        return $this->generateMediaUniqId($media).'.'.$media->getBinaryContent()->guessExtension();
    }

    /**
     * @param MediaInterface $media
     *
     * @return string
     */
    protected function generateMediaUniqId(MediaInterface $media): string
    {
        return sha1($media->getName().uniqid().rand(11111, 99999));
    }

    /**
     * @param MediaInterface $media
     * @param string $format
     * @param string $mode
     * @param array $headers
     * @return Response
     */
    public function getDownloadResponse(MediaInterface $media, string $format, string $mode, array $headers = []): Response
    {
        // build the default headers
        $headers = array_merge([
            'Content-Type' => $media->getContentType(),
            'Content-Disposition' => sprintf('attachment; filename="%s"', $media->getMetadataValue('filename')),
        ], $headers);

        if (!in_array($mode, ['http', 'X-Sendfile', 'X-Accel-Redirect'])) {
            throw new RuntimeException('Invalid mode provided');
        }

        if ('reference' === $format) {
            $file = $this->getReferenceFile($media);
        } else {
            $file = $this->getFilesystem()->get($this->generatePrivateUrl($media, $format));
        }

        return new StreamedResponse(function () use ($file) {
            echo $file->getContent();
        }, 200, $headers);
    }

    /**
     * @param MediaInterface $media
     * @param string $format
     * @param array $headers
     * @return Response
     */
    public function getViewResponse(MediaInterface $media, string $format, array $headers = []): Response
    {
        // build the default headers
        $headers = array_merge([
            'Content-Type' => $media->getContentType(),
            'Content-Disposition' => sprintf('inline; filename="%s"', $media->getMetadataValue('filename')),
        ], $headers);

        if ('reference' === $format) {
            $file = $this->getReferenceFile($media);
        } else {
            $file = $this->getFilesystem()->get($this->generatePrivateUrl($media, $format));
        }

        return new StreamedResponse(function () use ($file) {
            echo $file->getContent();
        }, 200, $headers);
    }
}
