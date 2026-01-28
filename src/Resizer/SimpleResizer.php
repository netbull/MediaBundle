<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Resizer;

use Gaufrette\File;
use Imagine\Exception\InvalidArgumentException;
use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use Imagine\Image\ManipulatorInterface;
use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Metadata\MetadataBuilderInterface;
use RuntimeException;

class SimpleResizer implements ResizerInterface
{
    protected ImagineInterface $adapter;

    protected int $mode;

    protected MetadataBuilderInterface $metadata;

    public function __construct(ImagineInterface $adapter, int $mode, MetadataBuilderInterface $metadata)
    {
        $this->adapter = $adapter;
        $this->mode = $mode;
        $this->metadata = $metadata;
    }

    public function resize(MediaInterface $media, File $in, File $out, string $format, array $settings): void
    {
        if (!isset($settings['width'])) {
            throw new RuntimeException(\sprintf('Width parameter is missing in context "%s" for provider "%s"', $media->getContext(), $media->getProviderName()));
        }

        $image = $this->adapter->load($in->getContent());

        switch ($media->getExtension()) {
            case 'gif':
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

        $out->setContent($content, $this->metadata->get($out->getName()));
    }

    public function getBox(MediaInterface $media, array $settings): Box
    {
        $size = $media->getBox();

        if (null === $settings['width'] && null === $settings['height']) {
            throw new RuntimeException(\sprintf('Width/Height parameter is missing in context "%s" for provider "%s". Please add at least one parameter.', $media->getContext(), $media->getProviderName()));
        }

        if (null === $settings['height']) {
            $settings['height'] = (int) ($settings['width'] * $size->getHeight() / $size->getWidth());
        }

        if (null === $settings['width']) {
            $settings['width'] = (int) ($settings['height'] * $size->getWidth() / $size->getHeight());
        }

        return $this->computeBox($media, $settings);
    }

    private function computeBox(MediaInterface $media, array $settings): Box
    {
        if (ManipulatorInterface::THUMBNAIL_INSET !== $this->mode && ManipulatorInterface::THUMBNAIL_OUTBOUND !== $this->mode) {
            throw new InvalidArgumentException('Invalid mode specified');
        }

        $size = $media->getBox();

        $ratios = [
            $settings['width'] / $size->getWidth(),
            $settings['height'] / $size->getHeight(),
        ];

        if (ManipulatorInterface::THUMBNAIL_INSET === $this->mode) {
            $ratio = min($ratios);
        } else {
            $ratio = max($ratios);
        }

        return $size->scale($ratio);
    }
}
