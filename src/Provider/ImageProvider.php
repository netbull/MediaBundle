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
     * @var ImagineInterface
     */
    protected ImagineInterface $imagineAdapter;

    /**
     * @param string $name
     * @param Filesystem $filesystem
     * @param CdnInterface $cdn
     * @param ThumbnailInterface $thumbnail
     * @param RouterInterface $router
     * @param SimpleSignatureHasher $simpleSignatureHasher
     * @param ImagineInterface $adapter
     * @param array $allowedExtensions
     * @param array $allowedMimeTypes
     * @param MetadataBuilderInterface|null $metadata
     */
    public function __construct(string $name, Filesystem $filesystem, CdnInterface $cdn, ThumbnailInterface $thumbnail, RouterInterface $router, SimpleSignatureHasher $simpleSignatureHasher, ImagineInterface $adapter, array $allowedExtensions = [], array $allowedMimeTypes = [], MetadataBuilderInterface $metadata = null)
    {
        parent::__construct($name, $filesystem, $cdn, $thumbnail, $router, $simpleSignatureHasher, $allowedExtensions, $allowedMimeTypes, $metadata);

        $this->imagineAdapter = $adapter;
    }

    /**
     * {@inheritdoc}
     */
    public function getHelperProperties($media, string $format, array $options = [])
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
     * {@inheritdoc}
     */
    public function getReferenceImage($media)
    {
        return sprintf('%s/%s',
            $this->generatePath($media),
            $media instanceof MediaInterface ?$media->getProviderReference() : $media['providerReference']
        );
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
                    'accept' => 'image/*'
                ]
            ], $options))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildMediaType(FormBuilderInterface $formBuilder, array $options = [])
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
    protected function fixOrientation(MediaInterface $media)
    {
        $binaryContent = $media->getBinaryContent();
        if ($binaryContent === null) {
            return;
        }

        if (function_exists('exif_read_data') && 'image/jpeg' === $binaryContent->getMimeType()) {
            $filename = $binaryContent->getRealPath();
            try {
                $exif = exif_read_data($filename);
            } catch (Exception $e) {
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
     * {@inheritdoc}
     */
    protected function doTransform(MediaInterface $media)
    {
        parent::doTransform($media);

        $this->fixOrientation($media);

        if (!is_object($media->getBinaryContent()) && !$media->getBinaryContent()) {
            return;
        }

        try {
            $image = $this->imagineAdapter->open($media->getBinaryContent()->getPathname());
        } catch (RuntimeException $e) {
            return;
        }

        $size = $image->getSize();

        $media->setWidth($size->getWidth());
        $media->setHeight($size->getHeight());
    }

    /**
     * {@inheritdoc}
     */
    public function updateMetadata(MediaInterface $media, $force = true)
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
        } catch (LogicException $e) {
            $media->setSize(0);
            $media->setWidth(0);
            $media->setHeight(0);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function generatePublicUrl($media, $format)
    {
        if ('reference' === $format) {
            $path = $this->getReferenceImage($media);
        } else {
            $path = $this->thumbnail->generatePublicUrl($this, $media, $format);
        }

        return $this->getCdn()->getPath($path);
    }

    /**
     * {@inheritdoc}
     */
    public function generatePrivateUrl(MediaInterface $media, $format)
    {
        return $this->thumbnail->generatePrivateUrl($this, $media, $format);
    }
}
