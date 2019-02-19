<?php

namespace NetBull\MediaBundle\Thumbnail;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Process\PhpExecutableFinder;

use NetBull\MediaBundle\Model\MediaInterface;
use NetBull\MediaBundle\Provider\MediaProviderInterface;

/**
 * Class FormatThumbnail
 * @package NetBull\MediaBundle\Thumbnail
 */
class FormatThumbnail implements ThumbnailInterface
{
    /**
     * @var string
     */
    private $defaultFormat;

    /**
     * @var Kernel
     */
    private $kernel;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * FormatThumbnail constructor.
     * @param $defaultFormat
     * @param Kernel $kernel
     * @param LoggerInterface $logger
     */
    public function __construct($defaultFormat, Kernel $kernel, LoggerInterface $logger)
    {
        $this->defaultFormat = $defaultFormat;
        $this->kernel = $kernel;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function generatePublicUrl(MediaProviderInterface $provider, $media, $format)
    {
        if ('reference' === $format) {
            $path = $provider->getReferenceImage($media);
        } else {
            $id = ($media instanceof MediaInterface)?$media->getId():$media['id'];
            $path = sprintf('%s/thumb_%s_%s.%s', $provider->generatePath($media), $id, $format, $this->getExtension($media));
        }
        return $path;
    }

    /**
     * {@inheritdoc}
     */
    public function generatePrivateUrl(MediaProviderInterface $provider, MediaInterface $media, $format)
    {
        $id = ($media instanceof MediaInterface)?$media->getId():$media['id'];
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
                $shouldWait = 'normal' === $shortFormat || 'prod' !== $this->kernel->getEnvironment();

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
        $root_dir = $this->kernel->getProjectDir();

        $phpPath = (new PhpExecutableFinder)->find();
        if (!$phpPath) {
            return;
        }

        $process = new Process([
            $phpPath,
            "$root_dir/bin/console",
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
     * {@inheritdoc}
     */
    public function generateByFormat(MediaProviderInterface $provider, MediaInterface $media, $format2)
    {
        if (!$provider->requireThumbnails()) {
            return;
        }

        $referenceFile = $provider->getReferenceFile($media);
        if (!$referenceFile->exists()) {
            $this->logger->info(sprintf('The reference file for [%d] doesn\'t exists', $media->getId()));
            return;
        }

        foreach ($provider->getFormats() as $format => $settings) {
            if (substr($format, 0, strlen($media->getContext())) === $media->getContext() &&
                $format2 === str_replace($media->getContext() . '_', '', $format)) {
                $provider->getResizer()->resize(
                    $media,
                    $referenceFile,
                    $provider->getFilesystem()->get($provider->generatePrivateUrl($media, $format), true),
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
            $ext = $this->defaultFormat;
        }

        return $ext;
    }
}
