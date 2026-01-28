<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Provider;

use Gaufrette\File;
use Gaufrette\FilesystemInterface;
use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Resizer\ResizerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Response;

interface MediaProviderInterface
{
    public function addFormat(string $name, array $format): void;

    /**
     * @return string|false
     */
    public function getFormat(string $name): array|false;

    /**
     * return true if the media related to the provider required thumbnails (generation).
     */
    public function requireThumbnails(): bool;

    /**
     * Generated thumbnails linked to the media, a thumbnail is a format used on the website.
     */
    public function generateThumbnails(MediaInterface $media): void;

    /**
     * Generated thumbnails linked to the media, a thumbnail is a format used on the website.
     */
    public function generateThumbnail(MediaInterface $media, string $format): void;

    /**
     * remove all linked thumbnails.
     */
    public function removeThumbnails(MediaInterface $media): void;

    public function getReferenceFile(array|MediaInterface $media): ?File;

    /**
     * return the correct format name : providerName_format.
     */
    public function getFormatName(array|MediaInterface $media, string $format): string;

    /**
     * return the reference image of the media, can be the video thumbnail or the original uploaded picture.
     *
     * @return string to the reference image
     */
    public function getReferenceImage(array|MediaInterface $media): string;

    public function preUpdate(MediaInterface $media): void;

    public function postUpdate(MediaInterface $media): void;

    public function preRemove(MediaInterface $media): void;

    public function postRemove(MediaInterface $media): void;

    public function postFlush(MediaInterface $media): void;

    public function prePersist(MediaInterface $media): void;

    public function postPersist(MediaInterface $media): void;

    public function getHelperProperties(array|MediaInterface $media, string $format, array $options = []): array;

    /**
     * Generate the media path.
     */
    public function generatePath(array|MediaInterface $media): string;

    /**
     * Generate the public path.
     */
    public function generatePublicUrl(array|MediaInterface $media, string $format): string;

    /**
     * Generate the secured url.
     */
    public function generateSecuredUrl(array|MediaInterface $media, string $format, string $identifier, int $expires = 300): string;

    /**
     * Generate the private path.
     */
    public function generatePrivateUrl(MediaInterface $media, string $format): string;

    public function getFormats(): array;

    public function setName(string $name);

    public function getName(): string;

    /**
     * Mode can be x-file.
     */
    public function getDownloadResponse(MediaInterface $media, string $format, string $mode, array $headers = []): Response;

    public function getViewResponse(MediaInterface $media, string $format, array $headers = []): Response;

    public function getResizer(): ?ResizerInterface;

    public function getFilesystem(): FilesystemInterface;

    public function getCdnPath(string $relativePath): string;

    public function transform(MediaInterface $media): void;

    public function buildMediaType(FormBuilderInterface $formBuilder, array $options = []): void;

    public function updateMetadata(MediaInterface $media, bool $force = false): void;
}
