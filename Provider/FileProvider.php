<?php

namespace NetBull\MediaBundle\Provider;

use Gaufrette\Filesystem;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Form\Extension\Core\Type\FileType;

use NetBull\MediaBundle\CDN\CDNInterface;
use NetBull\MediaBundle\Model\MediaInterface;
use NetBull\MediaBundle\Thumbnail\ThumbnailInterface;
use NetBull\MediaBundle\Metadata\MetadataBuilderInterface;

/**
 * Class FileProvider
 * @package NetBull\MediaBundle\Provider
 */
class FileProvider extends BaseProvider
{
    /**
     * @var array
     */
    protected $allowedExtensions;

    /**
     * @var array
     */
    protected $allowedMimeTypes;

    /**
     * @var MetadataBuilderInterface
     */
    protected $metadata;

    /**
     * @param string                    $name
     * @param Filesystem                $filesystem
     * @param CDNInterface              $cdn
     * @param ThumbnailInterface        $thumbnail
     * @param array                     $allowedExtensions
     * @param array                     $allowedMimeTypes
     * @param MetadataBuilderInterface  $metadata
     */
    public function __construct($name, Filesystem $filesystem, CDNInterface $cdn, ThumbnailInterface $thumbnail, array $allowedExtensions = [], array $allowedMimeTypes = [], MetadataBuilderInterface $metadata = null)
    {
        parent::__construct($name, $filesystem, $cdn, $thumbnail);

        $this->allowedExtensions    = $allowedExtensions;
        $this->allowedMimeTypes     = $allowedMimeTypes;
        $this->metadata             = $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getReferenceImage($media)
    {
        return sprintf('%s/%s',
            $this->generatePath($media),
            ($media instanceof MediaInterface) ? $media->getProviderReference() : $media['providerReference']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getReferenceFile($media)
    {
        return $this->getFilesystem()->get($this->getReferenceImage($media), true);
    }

    /**
     * {@inheritdoc}
     */
    public function buildMediaType(FormBuilderInterface $formBuilder, array $options = [])
    {
        if (isset($options['main_field'])) {
            unset($options['main_field']);
        }
        $formBuilder->add('newBinaryContent', FileType::class, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function buildShortMediaType(FormBuilderInterface $formBuilder, array $options = [])
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
     * {@inheritdoc}
     */
    public function postPersist(MediaInterface $media)
    {
        if ($media->getBinaryContent() === null) {
            return;
        }

        $this->setFileContents($media);
        $this->generateThumbnails($media);
    }

    /**
     * {@inheritdoc}
     */
    public function postUpdate(MediaInterface $media)
    {
        if (!$media->getBinaryContent() instanceof \SplFileInfo) {
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
        $this->generateThumbnails($media);
        $media->resetBinaryContent();
    }

    /**
     * @throws \RuntimeException
     *
     * @param MediaInterface $media
     */
    protected function fixBinaryContent(MediaInterface $media)
    {
        if ($media->getNewBinaryContent() === null) {
            return;
        }

        // if the binary content is a filename => convert to a valid File
        if (!$media->getNewBinaryContent() instanceof File) {
            if (!is_file($media->getBinaryContent())) {
                throw new \RuntimeException('The file does not exist : '.$media->getNewBinaryContent());
            }

            $binaryContent = new File($media->getNewBinaryContent());

            $media->setNewBinaryContent($binaryContent);
        }
    }

    /**
     * @throws \RuntimeException
     *
     * @param MediaInterface $media
     */
    protected function fixFilename(MediaInterface $media)
    {
        if ($media->getNewBinaryContent() instanceof UploadedFile) {
            $media->setName($media->getName() ?: $media->getNewBinaryContent()->getClientOriginalName());
            $media->setMetadataValue('filename', $media->getNewBinaryContent()->getClientOriginalName());
        } elseif ($media->getNewBinaryContent() instanceof File) {
            $media->setName($media->getName() ?: $media->getNewBinaryContent()->getBasename());
            $media->setMetadataValue('filename', $media->getNewBinaryContent()->getBasename());
        }

        // this is the original name
        if (!$media->getName()) {
            throw new \RuntimeException('Please define a valid media\'s name');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doTransform(MediaInterface $media)
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
     * {@inheritdoc}
     */
    public function updateMetadata(MediaInterface $media, $force = true)
    {
        // this is now optimized at all!!!
        $path = tempnam(sys_get_temp_dir(), 'update_metadata');
        $fileObject = new \SplFileObject($path, 'w');
        $fileObject->fwrite($this->getReferenceFile($media)->getContent());

        $media->setSize($fileObject->getSize());
    }

    /**
     * {@inheritdoc}
     */
    public function generatePublicUrl($media, $format)
    {
        if ('reference' === $format) {
            $path = $this->getReferenceImage($media);
        } else {
            $path = sprintf('../files/%s/file.png', $format);
        }

        return $this->getCdn()->getPath($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getHelperProperties($media, $format, array $options = [])
    {
        if ($media instanceof MediaInterface) {
            $data = [
                'title'    => $media->getName(),
                'thumbnail'   => $this->getReferenceImage($media),
                'file'        => $this->getReferenceImage($media),
            ];
        } else {
            $data = [
                'title'    => $media['name'],
                'thumbnail'   => $this->getReferenceImage($media),
                'file'        => $this->getReferenceImage($media),
            ];
        }

        return array_merge($data, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function generatePrivateUrl(MediaInterface $media, $format)
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
     * @param string $contents path to contents, defaults to MediaInterface BinaryContent
     */
    protected function setFileContents(MediaInterface $media, $contents = null)
    {
        $file = $this->getFilesystem()->get(sprintf('%s/%s', $this->generatePath($media), $media->getProviderReference()), true);

        if (!$contents) {
            $contents = $media->getBinaryContent()->getRealPath();
        }

        $metadata = $this->metadata ? $this->metadata->get($media, $file->getName()) : [];
        $file->setContent(file_get_contents($contents), $metadata);
    }

    /**
     * @param MediaInterface $media
     *
     * @return string
     */
    protected function generateReferenceName(MediaInterface $media)
    {
        return $this->generateMediaUniqId($media).'.'.$media->getBinaryContent()->guessExtension();
    }

    /**
     * @param MediaInterface $media
     *
     * @return string
     */
    protected function generateMediaUniqId(MediaInterface $media)
    {
        return sha1($media->getName().uniqid().rand(11111, 99999));
    }

    /**
     * {@inheritdoc}
     */
    public function getDownloadResponse(MediaInterface $media, $format, $mode, array $headers = [])
    {
        // build the default headers
        $headers = array_merge([
            'Content-Type'          => $media->getContentType(),
            'Content-Disposition'   => sprintf('attachment; filename="%s"', $media->getMetadataValue('filename')),
        ], $headers);

        if (!in_array($mode, ['http', 'X-Sendfile', 'X-Accel-Redirect'])) {
            throw new \RuntimeException('Invalid mode provided');
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
}
