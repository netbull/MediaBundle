<?php

namespace NetBull\MediaBundle\Provider;

use Exception;
use Gaufrette\Filesystem;
use Imagine\Image\ImagineInterface;
use LogicException;
use NetBull\MediaBundle\Signature\SimpleSignatureHasher;
use RuntimeException;
use SplFileObject;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use NetBull\MediaBundle\Cdn\CdnInterface;
use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Thumbnail\ThumbnailInterface;
use NetBull\MediaBundle\Metadata\MetadataBuilderInterface;
use Symfony\Component\Routing\RouterInterface;

class ImageProvider extends FileProvider
{
    /**
     * @param string $name
     * @param Filesystem $filesystem
     * @param CdnInterface $cdn
     * @param ThumbnailInterface $thumbnail
     * @param RouterInterface $router
     * @param SimpleSignatureHasher $simpleSignatureHasher
     * @param ImagineInterface $imagineAdapter
     * @param array $allowedExtensions
     * @param array $allowedMimeTypes
     * @param MetadataBuilderInterface|null $metadata
     */
    public function __construct(
        string $name,
        Filesystem $filesystem,
        CdnInterface $cdn,
        ThumbnailInterface $thumbnail,
        RouterInterface $router,
        SimpleSignatureHasher $simpleSignatureHasher,
        protected ImagineInterface $imagineAdapter,
        array $allowedExtensions = [],
        array $allowedMimeTypes = [],
        MetadataBuilderInterface $metadata = null
    ) {
        parent::__construct($name, $filesystem, $cdn, $thumbnail, $router, $simpleSignatureHasher, $allowedExtensions, $allowedMimeTypes, $metadata);
    }

    /**
     * @param array|MediaInterface $media
     * @param string $format
     * @param array $options
     * @return array
     */
    public function getHelperProperties(array|MediaInterface $media, string $format, array $options = []): array
    {
        if($media instanceof MediaInterface){
            if ($format !== 'reference') {
                $resizerFormat = $this->getFormat($format);
                if (false === $resizerFormat) {
                    throw new RuntimeException(sprintf('The image format "%s" is not defined.
                        Is the format registered in your ``media`` configuration?', $format));
                }
            }
            $data = [
                'alt' => $media->getName(),
                'src' => $this->generatePublicUrl($media, $format),
            ];
        }else{
            $alt = '';
            if(isset($media['caption'])){
                $alt = $media['caption'];
            }else if (isset($media['name'])) {
                $alt = $media['name'];
            }
            $data = [
                'alt' => $alt,
                'src' => $this->generatePublicUrl($media, $format),
            ];
        }

        return array_merge($data, $options);
    }

    /**
     * @param array|MediaInterface $media
     * @return string
     */
    public function getReferenceImage(array|MediaInterface $media): string
    {
        return sprintf('%s/%s',
            $this->generatePath($media),
            $media instanceof MediaInterface ?$media->getProviderReference() : $media['providerReference']
        );
    }

    /**
     * @param FormBuilderInterface $formBuilder
     * @param array $options
     * @return void
     */
    public function buildShortMediaType(FormBuilderInterface $formBuilder, array $options = []): void
    {
        $formBuilder
            ->add('newBinaryContent', FileType::class, array_merge([
                'attr' => [
                    'class' => 'image-upload',
                    'accept' => 'image/*'
                ]
            ], $options))
        ;
    }

    /**
     * @param FormBuilderInterface $formBuilder
     * @param array $options
     * @return void
     */
    public function buildMediaType(FormBuilderInterface $formBuilder, array $options = []): void
    {
        $mainField = $options['main_field'];
        unset($options['main_field']);
        unset($options['locale']);

        $formBuilder
            ->add('newBinaryContent', FileType::class, array_merge([
                'attr' => [
                    'class' => 'hidden image-upload',
                    'accept' => 'image/*'
                ]
            ], $options))
            ->add('caption', TextType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'Caption'
                ],
                'required' => false
            ])
        ;

        if ($mainField) {
            $formBuilder->add('main', RadioType::class, [
                'label' => 'Main',
                'attr'  => [
                    'class' => 'image-main'
                ],
                'required'  => false
            ]);
        }
    }

    /**
     * @param MediaInterface $media
     */
    protected function fixOrientation(MediaInterface $media): void
    {
        $binaryContent = $media->getBinaryContent();
        if ($binaryContent === null) {
            return;
        }

        if (function_exists('exif_read_data') && 'image/jpeg' === $binaryContent->getMimeType()) {
            $filename = $binaryContent->getRealPath();
            try {
                $exif = exif_read_data($filename);
            } catch (Exception) {
                return;
            }

            if ($exif && isset($exif['Orientation'])) {
                $orientation = $exif['Orientation'];
                if ($orientation != 1) {
                    $img = imagecreatefromjpeg($filename);
                    $deg = 0;
                    switch ($orientation) {
                        case 3:
                            $deg = 180;
                            break;
                        case 6:
                            $deg = 270;
                            break;
                        case 8:
                            $deg = 90;
                            break;
                    }
                    if ($deg) {
                        $img = imagerotate($img, $deg, 0);
                    }
                    // then rewrite the rotated image back to the disk as $filename
                    imagejpeg($img, $filename, 95);
                }
            }
        }
    }

    /**
     * @param MediaInterface $media
     * @return void
     */
    protected function doTransform(MediaInterface $media): void
    {
        parent::doTransform($media);

        $this->fixOrientation($media);

        if (!is_object($media->getBinaryContent()) && !$media->getBinaryContent()) {
            return;
        }

        try {
            $image = $this->imagineAdapter->open($media->getBinaryContent()->getPathname());
        } catch (RuntimeException) {
            return;
        }

        $size = $image->getSize();

        $media->setWidth($size->getWidth());
        $media->setHeight($size->getHeight());
    }

    /**
     * @param MediaInterface $media
     * @param bool $force
     * @return void
     */
    public function updateMetadata(MediaInterface $media, bool $force = true): void
    {
        try {
            // this is now optimized at all!!!
            $path       = tempnam(sys_get_temp_dir(), 'update_metadata');
            $fileObject = new SplFileObject($path, 'w');
            $fileObject->fwrite($this->getReferenceFile($media)->getContent());

            $image = $this->imagineAdapter->open($fileObject->getPathname());
            $size  = $image->getSize();

            $media->setSize($fileObject->getSize());
            $media->setWidth($size->getWidth());
            $media->setHeight($size->getHeight());
        } catch (LogicException) {
            $media->setSize(0);
            $media->setWidth(0);
            $media->setHeight(0);
        }
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
            $path = $this->thumbnail->generatePublicUrl($this, $media, $format);
        }

        return $this->getCdn()->getPath($path);
    }

    /**
     * @param MediaInterface $media
     * @param string $format
     * @return string
     */
    public function generatePrivateUrl(MediaInterface $media, string $format): string
    {
        return $this->thumbnail->generatePrivateUrl($this, $media, $format);
    }
}
