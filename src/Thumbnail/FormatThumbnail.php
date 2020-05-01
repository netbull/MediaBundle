<?php

namespace NetBull\MediaBundle\Thumbnail;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpExecutableFinder;
use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Provider\MediaProviderInterface;

/**
 * Class FormatThumbnail
 * @package NetBull\MediaBundle\Thumbnail
 */
class FormatThumbnail implements ThumbnailInterface
{
    /**
     * @var bool
     */
    private $isProd;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * FormatThumbnail constructor.
     * @param ParameterBagInterface $parameterBag
     * @param LoggerInterface $logger
     */
    public function __construct(ParameterBagInterface $parameterBag, LoggerInterface $logger)
    {
        $this->isProd = 'prod' === $parameterBag->get('kernel.environment');
        $this->projectDir = $parameterBag->get('kernel.project_dir');
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function generatePublicUrl(MediaProviderInterface $provider, $media, string $format)
    {
        if ('reference' === $format) {
            $path = $provider->getReferenceImage($media);
        } else {
            $id = $media instanceof MediaInterface ? $media->getId() : $media['id'];
            $path = sprintf('%s/thumb_%s_%s.%s', $provider->generatePath($media), $id, $format, $this->getExtension($media));
        }
        return $path;
    }

    /**
     * {@inheritdoc}
     */
    public function generatePrivateUrl(MediaProviderInterface $provider, MediaInterface $media, string $format)
    {
        $id = $media instanceof MediaInterface ? $media->getId() : $media['id'];
        return sprintf('%s/thumb_%s_%s.%s', $provider->generatePath($media), $id, $format, $this->getExtension($media));
    }

    /**
     * {@inheritdoc}
     */
    public function generate(MediaProviderInterface $provider, MediaInterface $media)
    {
        if (!$provider->requireThumbnails()) {
            return;
        }

        $referenceFile = $provider->getReferenceFile($media);

        if (!$referenceFile->exists()) {
            return;
        }

        foreach ($provider->getFormats() as $format => $settings) {
            if (substr($format, 0, strlen($media->getContext())) === $media->getContext()) {
                $shortFormat = str_replace($media->getContext() . '_', '', $format);
                $shouldWait = 'normal' === $shortFormat || !$this->isProd;

                $this->forkProcess($media, $shortFormat, $shouldWait);
            }
        }
    }

    /**
     * @param MediaInterface $media
     * @param string $shortFormat
     * @param bool $wait
     */
    private function forkProcess(MediaInterface $media, string $shortFormat, bool $wait = false)
    {
        $phpPath = (new PhpExecutableFinder)->find();
        if (!$phpPath) {
            return;
        }

        $process = new Process([
            $phpPath,
            "$this->projectDir/bin/console",
            'netbull:media:create-thumbnail',
            $media->getId(),
            $shortFormat
        ]);
        $process->run();
        if ($wait) {
            $process->wait();
        }

        if (!$process->isSuccessful()) {
            $this->logger->error(sprintf('Creating size [%s] for [%d] failed! ["%s"]', $shortFormat, $media->getId(), $process->getOutput()));
        } else {
            $this->logger->info(sprintf('Created size [%s] for [%d].', $shortFormat, $media->getId()));
        }
    }

    /**
     * @param MediaProviderInterface $provider
     * @param MediaInterface $media
     * @param string $format
     * @return string|void
     */
    public function generateByFormat(MediaProviderInterface $provider, MediaInterface $media, string $format)
    {
        if (!$provider->requireThumbnails()) {
            return;
        }

        $referenceFile = $provider->getReferenceFile($media);
        if (!$referenceFile->exists()) {
            $this->logger->info(sprintf('The reference file for [%d] doesn\'t exists', $media->getId()));
            return;
        }

        foreach ($provider->getFormats() as $providerFormat => $settings) {
            if (
                substr($providerFormat, 0, strlen($media->getContext())) === $media->getContext() &&
                $format === str_replace($media->getContext() . '_', '', $providerFormat)
            ) {
                $provider->getResizer()->resize(
                    $media,
                    $referenceFile,
                    $provider->getFilesystem()->get($provider->generatePrivateUrl($media, $providerFormat), true),
                    $this->getExtension($media),
                    $settings
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(MediaProviderInterface $provider, MediaInterface $media)
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
     * @param array|MediaInterface $media
     * @return string the file extension for the $media, or the $defaultExtension if not available
     */
    protected function getExtension($media)
    {
        $ext = ($media instanceof MediaInterface)?$media->getExtension():pathinfo($media['providerReference'], PATHINFO_EXTENSION);
        if (!is_string($ext) || strlen($ext) < 3) {
            $ext = 'jpg';
        }

        return $ext;
    }
}
