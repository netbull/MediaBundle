<?php

namespace NetBull\MediaBundle\Resizer;

use Gaufrette\File;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Exception\InvalidArgumentException;

use NetBull\MediaBundle\Model\MediaInterface;
use NetBull\MediaBundle\Metadata\MetadataBuilderInterface;

/**
 * Class SimpleResizer
 * @package NetBull\MediaBundle\Resizer
 */
class SimpleResizer implements ResizerInterface
{
    /**
     * @var ImagineInterface
     */
    protected $adapter;

    /**
     * @var string
     */
    protected $mode;

    /**
     * @var MetadataBuilderInterface
     */
    protected $metadata;

    /**
     * @param ImagineInterface          $adapter
     * @param MetadataBuilderInterface  $metadata
     * @param string                    $mode
     */
    public function __construct(ImagineInterface $adapter, $mode, MetadataBuilderInterface $metadata)
    {
        $this->adapter  = $adapter;
        $this->mode     = $mode;
        $this->metadata = $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function resize(MediaInterface $media, File $in, File $out, $format, array $settings)
    {
        if (!isset($settings['width'])) {
            throw new \RuntimeException(sprintf('Width parameter is missing in context "%s" for provider "%s"', $media->getContext(), $media->getProviderName()));
        }

        $image = $this->adapter->load($in->getContent());

        switch ($media->getExtension()) {
            case 'gif':
            case 'png':
                $image->layers()->coalesce();
                $formatSettings = [
                    'flatten' => false,
                    'animated' => true,
                ];
                break;
            case 'jpeg':
            case 'jpg':
                $formatSettings = [
                    'jpeg_quality' => $settings['quality'],
                ];
                break;
            default:
                $formatSettings = [];
                break;
        }

        $content = $image
            ->thumbnail($this->getBox($media, $settings), $this->mode)
            ->get($format, $formatSettings);

        $out->setContent($content, $this->metadata->get($media, $out->getName()));
    }

    /**
     * {@inheritdoc}
     */
    public function getBox(MediaInterface $media, array $settings)
    {
        $size = $media->getBox();

        if (null === $settings['width'] && null === $settings['height']) {
            throw new \RuntimeException(sprintf('Width/Height parameter is missing in context "%s" for provider "%s". Please add at least one parameter.', $media->getContext(), $media->getProviderName()));
        }

        if (null === $settings['height']) {
            $settings['height'] = (int) ($settings['width'] * $size->getHeight() / $size->getWidth());
        }

        if (null === $settings['width']) {
            $settings['width'] = (int) ($settings['height'] * $size->getWidth() / $size->getHeight());
        }

        return $this->computeBox($media, $settings);
    }

    /**
     * @throws InvalidArgumentException
     *
     * @param MediaInterface $media
     * @param array          $settings
     *
     * @return Box
     */
    private function computeBox(MediaInterface $media, array $settings)
    {
        if ($this->mode !== ImageInterface::THUMBNAIL_INSET && $this->mode !== ImageInterface::THUMBNAIL_OUTBOUND) {
            throw new InvalidArgumentException('Invalid mode specified');
        }

        $size = $media->getBox();

        $ratios = [
            $settings['width'] / $size->getWidth(),
            $settings['height'] / $size->getHeight(),
        ];

        if ($this->mode === ImageInterface::THUMBNAIL_INSET) {
            $ratio = min($ratios);
        } else {
            $ratio = max($ratios);
        }

        return $size->scale($ratio);
    }
}
