<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Thumbnail;

use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Message\GenerateThumbnailMessage;
use NetBull\MediaBundle\Provider\MediaProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class FormatThumbnail implements ThumbnailInterface
{
    /**
     * @param bool $async when true, each format is dispatched as a GenerateThumbnailMessage to the
     *                    message bus instead of being resized in-process. Route that message to an async transport
     *                    to offload resizing to a worker (memory is isolated per worker run); with no transport
     *                    configured Messenger handles it synchronously. Toggle via `netbull_media.thumbnail.async`.
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $messageBus,
        private readonly bool $async = false,
    ) {
    }

    public function generatePublicUrl(MediaProviderInterface $provider, array|MediaInterface $media, string $format): string
    {
        if ('reference' === $format) {
            $path = $provider->getReferenceImage($media);
        } else {
            $id = $media instanceof MediaInterface ? $media->getId() : $media['id'];
            $path = \sprintf('%s/thumb_%s_%s.%s', $provider->generatePath($media), $id, $format, $this->getExtension($media));
        }

        return $path;
    }

    public function generatePrivateUrl(MediaProviderInterface $provider, MediaInterface $media, string $format): string
    {
        return \sprintf('%s/thumb_%s_%s.%s', $provider->generatePath($media), $media->getId(), $format, $this->getExtension($media));
    }

    public function generate(MediaProviderInterface $provider, MediaInterface $media): void
    {
        if (!$provider->requireThumbnails()) {
            return;
        }

        if ($this->async) {
            foreach ($provider->getFormats() as $format => $settings) {
                if (!str_starts_with($format, $media->getContext())) {
                    continue;
                }

                $shortFormat = str_replace($media->getContext() . '_', '', $format);
                $this->messageBus->dispatch(new GenerateThumbnailMessage((int) $media->getId(), $shortFormat));
            }

            return;
        }

        $referenceFile = $provider->getReferenceFile($media);

        if (!$referenceFile || !$referenceFile->exists()) {
            return;
        }

        foreach ($provider->getFormats() as $format => $settings) {
            if (!str_starts_with($format, $media->getContext())) {
                continue;
            }

            $provider->getResizer()->resize(
                $media,
                $referenceFile,
                $provider->getFilesystem()->get($provider->generatePrivateUrl($media, $format), true),
                $this->getExtension($media),
                $settings,
            );
        }
    }

    public function generateByFormat(MediaProviderInterface $provider, MediaInterface $media, string $format): void
    {
        if (!$provider->requireThumbnails()) {
            return;
        }

        $referenceFile = $provider->getReferenceFile($media);
        if (!$referenceFile || !$referenceFile->exists()) {
            $this->logger->info(\sprintf('The reference file for [%d] doesn\'t exists', $media->getId()));

            return;
        }

        foreach ($provider->getFormats() as $providerFormat => $settings) {
            if (
                str_starts_with($providerFormat, $media->getContext())
                && $format === str_replace($media->getContext() . '_', '', $providerFormat)
            ) {
                $provider->getResizer()->resize(
                    $media,
                    $referenceFile,
                    $provider->getFilesystem()->get($provider->generatePrivateUrl($media, $providerFormat), true),
                    $this->getExtension($media),
                    $settings,
                );
            }
        }
    }

    public function delete(MediaProviderInterface $provider, MediaInterface $media): void
    {
        // delete the different formats
        foreach ($provider->getFormats() as $format => $definition) {
            $path = $provider->generatePrivateUrl($media, $format);
            if ($path && $provider->getFilesystem()->has($path)) {
                $provider->getFilesystem()->delete($path);
            }
        }
    }

    /**
     * @return string the file extension for the $media, or the $defaultExtension if not available
     */
    protected function getExtension(array|MediaInterface $media): string
    {
        $ext = ($media instanceof MediaInterface) ? $media->getExtension() : pathinfo($media['providerReference'], \PATHINFO_EXTENSION);
        if (!\is_string($ext) || \strlen($ext) < 3) {
            $ext = 'jpg';
        }

        return $ext;
    }
}
