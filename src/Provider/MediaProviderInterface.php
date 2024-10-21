<?php

namespace NetBull\MediaBundle\Provider;

use Gaufrette\File;
use Gaufrette\FilesystemInterface;
use Symfony\Component\Form\FormBuilderInterface;
use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Resizer\ResizerInterface;
use Symfony\Component\HttpFoundation\Response;

interface MediaProviderInterface
{
    /**
     * @param string $name
     * @param array $format
     * @return void
     */
    public function addFormat(string $name, array $format): void;

    /**
     * @param string $name
     * @return string|false
     */
    public function getFormat(string $name): array|false;

    /**
     * return true if the media related to the provider required thumbnails (generation).
     *
     * @return bool
     */
    public function requireThumbnails(): bool;

    /**
     * Generated thumbnails linked to the media, a thumbnail is a format used on the website.
     *
     * @param MediaInterface $media
     */
    public function generateThumbnails(MediaInterface $media): void;

    /**
     * Generated thumbnails linked to the media, a thumbnail is a format used on the website.
     *
     * @param MediaInterface $media
     * @param string $format
     */
    public function generateThumbnail(MediaInterface $media, string $format): void;

    /**
     * remove all linked thumbnails.
     *
     * @param MediaInterface $media
     */
    public function removeThumbnails(MediaInterface $media): void;

    /**
     * @param array|MediaInterface $media
     * @return File|null
     */
    public function getReferenceFile(array|MediaInterface $media): ?File;

    /**
     * return the correct format name : providerName_format.
     *
     * @param array|MediaInterface $media
     * @param string $format
     *
     * @return string
     */
    public function getFormatName(array|MediaInterface $media, string $format): string;

    /**
     * return the reference image of the media, can be the video thumbnail or the original uploaded picture.
     *
     * @param array|MediaInterface $media
     *
     * @return string to the reference image
     */
    public function getReferenceImage(array|MediaInterface $media): string;

    /**
     * @param MediaInterface $media
     * @return void
     */
    public function preUpdate(MediaInterface $media): void;

    /**
     * @param MediaInterface $media
     * @return void
     */
    public function postUpdate(MediaInterface $media): void;

    /**
     * @param MediaInterface $media
     * @return void
     */
    public function preRemove(MediaInterface $media): void;

    /**
     * @param MediaInterface $media
     * @return void
     */
    public function postRemove(MediaInterface $media): void;

    /**
     * @param MediaInterface $media
     * @return void
     */
    public function postFlush(MediaInterface $media): void;

    /**
     * @param MediaInterface $media
     * @return void
     */
    public function prePersist(MediaInterface $media): void;

    /**
     * @param MediaInterface $media
     * @return void
     */
    public function postPersist(MediaInterface $media): void;

    /**
     * @param array|MediaInterface $media
     * @param string $format
     * @param array $options
     * @return array
     */
    public function getHelperProperties(array|MediaInterface $media, string $format, array $options = []): array;

    /**
     * Generate the media path.
     *
     * @param array|MediaInterface $media
     *
     * @return string
     */
    public function generatePath(array|MediaInterface $media): string;

    /**
     * Generate the public path.
     *
     * @param array|MediaInterface $media
     * @param string $format
     *
     * @return string
     */
    public function generatePublicUrl(array|MediaInterface $media, string $format): string;

    /**
     * Generate the secured url.
     *
     * @param array|MediaInterface $media
     * @param string $format
     * @param string $identifier
     * @param int $expires
     *
     * @return string
     */
    public function generateSecuredUrl(array|MediaInterface $media, string $format, string $identifier, int $expires = 300): string;

    /**
     * Generate the private path.
     *
     * @param MediaInterface $media
     * @param string $format
     *
     * @return string
     */
    public function generatePrivateUrl(MediaInterface $media, string $format): string;

    /**
     * @return array
     */
    public function getFormats(): array;

    /**
     * @param string $name
     */
    public function setName(string $name);

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * Mode can be x-file.
     *
     * @param MediaInterface $media
     * @param string $format
     * @param string $mode
     * @param array $headers
     *
     * @return Response
     */
    public function getDownloadResponse(MediaInterface $media, string $format, string $mode, array $headers = []): Response;

    /**
     * @param MediaInterface $media
     * @param string $format
     * @param array $headers
     *
     * @return Response
     */
    public function getViewResponse(MediaInterface $media, string $format, array $headers = []): Response;

    /**
     * @return ResizerInterface
     */
    public function getResizer(): ResizerInterface;

    /**
     * @return FilesystemInterface
     */
    public function getFilesystem(): FilesystemInterface;

    /**
     * @param string $relativePath
     * @return string
     */
    public function getCdnPath(string $relativePath): string;

    /**
     * @param MediaInterface $media
     * @return void
     */
    public function transform(MediaInterface $media): void;

    /**
     * @param FormBuilderInterface $formBuilder
     * @param array $options
     * @return void
     */
    public function buildMediaType(FormBuilderInterface $formBuilder, array $options = []): void;

    /**
     * @param MediaInterface $media
     * @param bool $force
     * @return void
     */
    public function updateMetadata(MediaInterface $media, bool $force = false): void;
}
