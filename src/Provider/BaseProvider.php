<?php

namespace NetBull\MediaBundle\Provider;

use Gaufrette\Filesystem;
use Gaufrette\FilesystemInterface;
use NetBull\MediaBundle\Cdn\CdnInterface;
use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Helpers\PathGenerator;
use NetBull\MediaBundle\Resizer\ResizerInterface;
use NetBull\MediaBundle\Thumbnail\ThumbnailInterface;

abstract class BaseProvider implements MediaProviderInterface
{
    /**
     * @var array
     */
    protected array $formats = [];

    /**
     * @var array
     */
    protected array $templates = [];

    /**
     * @var
     */
    protected mixed $resizer;

    /**
     * @var Filesystem
     */
    protected Filesystem $filesystem;

    /**
     * @var ThumbnailInterface
     */
    protected ThumbnailInterface $thumbnail;

    /**
     * @var string
     */
    protected string $name;

    /**
     * @var CdnInterface
     */
    protected CdnInterface $cdn;

    /**
     * @param string $name
     * @param Filesystem $filesystem
     * @param CdnInterface $cdn
     * @param ThumbnailInterface $thumbnail
     */
    public function __construct(string $name, Filesystem $filesystem, CdnInterface $cdn, ThumbnailInterface $thumbnail)
    {
        $this->name = $name;
        $this->filesystem = $filesystem;
        $this->cdn = $cdn;
        $this->thumbnail = $thumbnail;
    }

    /**
     * @param MediaInterface $media
     * @return void
     */
    abstract protected function doTransform(MediaInterface $media): void;

    /**
     * @param MediaInterface $media
     */
    final public function transform(MediaInterface $media): void
    {
        if (null === $media->getBinaryContent()) {
            return;
        }

        $this->doTransform($media);
    }

    /**
     * @param $name
     * @param $format
     */
    public function addFormat($name, $format): void
    {
        $this->formats[$name] = $format;
    }

    /**
     * @param string $name
     * @return array|false
     */
    public function getFormat(string $name): array|false
    {
        return $this->formats[$name] ?? false;
    }

    /**
     * @return bool
     */
    public function requireThumbnails(): bool
    {
        return $this->getResizer() !== null;
    }

    /**
     * @param MediaInterface $media
     * @return void
     */
    public function generateThumbnails(MediaInterface $media): void
    {
        $this->thumbnail->generate($this, $media);
    }

    /**
     * @param MediaInterface $media
     * @param string $format
     * @return void
     */
    public function generateThumbnail(MediaInterface $media, string $format): void
    {
        $this->thumbnail->generateByFormat($this, $media, $format);
    }

    /**
     * @param MediaInterface $media
     * @return void
     */
    public function removeThumbnails(MediaInterface $media): void
    {
        $this->thumbnail->delete($this, $media);
    }

    /**
     * @param array|MediaInterface $media
     * @param string $format
     * @return string
     */
    public function getFormatName(array|MediaInterface $media, string $format): string
    {
        if ('reference' === $format) {
            return 'reference';
        }

        $context = ($media instanceof MediaInterface) ? $media->getContext() : $media['context'];
        $baseName = $context . '_';
        if (str_starts_with($format, $baseName)) {
            return $format;
        }

        return $baseName.$format;
    }

    /**
     * @param MediaInterface $media
     * @return void
     */
    public function preRemove(MediaInterface $media): void
    {
        $path = $this->getReferenceImage($media);

        if ($path && $this->getFilesystem()->has($path)) {
            $this->getFilesystem()->delete($path);
        }

        if ($this->requireThumbnails()) {
            $this->thumbnail->delete($this, $media);
        }
    }

    /**
     * @param MediaInterface $media
     * @return void
     */
    public function postRemove(MediaInterface $media): void
    { }

    /**
     * @param MediaInterface $media
     * @return void
     */
    public function postFlush(MediaInterface $media): void
    { }

    /**
     * @param array|MediaInterface $media
     * @return string
     */
    public function generatePath(array|MediaInterface $media): string
    {
        return PathGenerator::generatePath($media);
    }

    /**
     * @return array
     */
    public function getFormats(): array
    {
        return $this->formats;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return ResizerInterface
     */
    public function getResizer(): ResizerInterface
    {
        return $this->resizer;
    }

    /**
     * @return FilesystemInterface
     */
    public function getFilesystem(): FilesystemInterface
    {
        return $this->filesystem;
    }

    /**
     * @return CdnInterface
     */
    public function getCdn(): CdnInterface
    {
        return $this->cdn;
    }

    /**
     * @param string $relativePath
     * @return string
     */
    public function getCdnPath(string $relativePath): string
    {
        return $this->getCdn()->getPath($relativePath);
    }

    /**
     * @param ResizerInterface $resizer
     * @return void
     */
    public function setResizer(ResizerInterface $resizer): void
    {
        $this->resizer = $resizer;
    }

    /**
     * @param MediaInterface $media
     * @return void
     */
    public function prePersist(MediaInterface $media): void
    { }

    /**
     * @param MediaInterface $media
     * @return void
     */
    public function preUpdate(MediaInterface $media): void
    { }

    /**
     * @param array $templates
     */
    public function setTemplates(array $templates): void
    {
        $this->templates = $templates;
    }

    /**
     * @return array
     */
    public function getTemplates(): array
    {
        return $this->templates;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getTemplate(string $name): mixed
    {
        return $this->templates[$name] ?? null;
    }

    /**
     * @param array|MediaInterface $media
     * @param string $format
     * @param array $options
     * @return array
     */
    public function getViewProperties(array|MediaInterface $media, string $format, array $options = [])
    {
        return $this->getHelperProperties($media, $format, $options);
    }
}
