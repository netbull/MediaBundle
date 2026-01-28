<?php

declare(strict_types=1);

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
    protected array $formats = [];

    protected array $templates = [];

    protected ?ResizerInterface $resizer = null;

    public function __construct(
        protected string $name,
        protected Filesystem $filesystem,
        protected CdnInterface $cdn,
        protected ThumbnailInterface $thumbnail,
    ) {
    }

    abstract protected function doTransform(MediaInterface $media): void;

    final public function transform(MediaInterface $media): void
    {
        if (null === $media->getBinaryContent()) {
            return;
        }

        $this->doTransform($media);
    }

    public function addFormat($name, $format): void
    {
        $this->formats[$name] = $format;
    }

    public function getFormat(string $name): array|false
    {
        return $this->formats[$name] ?? false;
    }

    public function requireThumbnails(): bool
    {
        return null !== $this->getResizer();
    }

    public function generateThumbnails(MediaInterface $media): void
    {
        $this->thumbnail->generate($this, $media);
    }

    public function generateThumbnail(MediaInterface $media, string $format): void
    {
        $this->thumbnail->generateByFormat($this, $media, $format);
    }

    public function removeThumbnails(MediaInterface $media): void
    {
        $this->thumbnail->delete($this, $media);
    }

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

        return $baseName . $format;
    }

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

    public function postRemove(MediaInterface $media): void
    {
    }

    public function postFlush(MediaInterface $media): void
    {
    }

    public function generatePath(array|MediaInterface $media): string
    {
        return PathGenerator::generatePath($media);
    }

    public function getFormats(): array
    {
        return $this->formats;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getResizer(): ?ResizerInterface
    {
        return $this->resizer;
    }

    public function getFilesystem(): FilesystemInterface
    {
        return $this->filesystem;
    }

    public function getCdn(): CdnInterface
    {
        return $this->cdn;
    }

    public function getCdnPath(string $relativePath): string
    {
        return $this->getCdn()->getPath($relativePath);
    }

    public function setResizer(ResizerInterface $resizer): void
    {
        $this->resizer = $resizer;
    }

    public function prePersist(MediaInterface $media): void
    {
    }

    public function preUpdate(MediaInterface $media): void
    {
    }

    public function setTemplates(array $templates): void
    {
        $this->templates = $templates;
    }

    public function getTemplates(): array
    {
        return $this->templates;
    }

    public function getTemplate(string $name): mixed
    {
        return $this->templates[$name] ?? null;
    }

    public function getViewProperties(array|MediaInterface $media, string $format, array $options = []): array
    {
        return $this->getHelperProperties($media, $format, $options);
    }
}
