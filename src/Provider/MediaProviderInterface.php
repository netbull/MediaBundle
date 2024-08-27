<?php

namespace NetBull\MediaBundle\Provider;

use Symfony\Component\Form\FormBuilderInterface;
use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Resizer\ResizerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface MediaProviderInterface
 * @package NetBull\MediaBundle\Provider
 */
interface MediaProviderInterface
{
    /**
     * @param string $name
     * @param array  $format
     */
    public function addFormat($name, $format);

    /**
     * return the format settings.
     *
     * @param string $name
     *
     * @return array|false the format settings
     */
    public function getFormat($name);

    /**
     * return true if the media related to the provider required thumbnails (generation).
     *
     * @return bool
     */
    public function requireThumbnails();

    /**
     * Generated thumbnails linked to the media, a thumbnail is a format used on the website.
     *
     * @param MediaInterface $media
     */
    public function generateThumbnails(MediaInterface $media);

    /**
     * Generated thumbnails linked to the media, a thumbnail is a format used on the website.
     *
     * @param MediaInterface $media
     * @param string $format
     */
    public function generateThumbnail(MediaInterface $media, $format);

    /**
     * remove all linked thumbnails.
     *
     * @param MediaInterface $media
     */
    public function removeThumbnails(MediaInterface $media);

    /**
     * @param array|MediaInterface $media
     */
    public function getReferenceFile($media);

    /**
     * return the correct format name : providerName_format.
     *
     * @param $media
     * @param string $format
     *
     * @return string
     */
    public function getFormatName($media, $format);

    /**
     * return the reference image of the media, can be the video thumbnail or the original uploaded picture.
     *
     * @param array|MediaInterface $media
     *
     * @return string to the reference image
     */
    public function getReferenceImage($media);

    /**
     * @param MediaInterface $media
     */
    public function preUpdate(MediaInterface $media);

    /**
     * @param MediaInterface $media
     */
    public function postUpdate(MediaInterface $media);

    /**
     * @param MediaInterface $media
     */
    public function preRemove(MediaInterface $media);

    /**
     * @param MediaInterface $media
     */
    public function postRemove(MediaInterface $media);

    /**
     * @param MediaInterface $media
     */
    public function postFlush(MediaInterface $media);

    /**
     * @param MediaInterface $media
     */
    public function prePersist(MediaInterface $media);

    /**
     * @param MediaInterface $media
     */
    public function postPersist(MediaInterface $media);

    /**
     * @param $media
     * @param string $format
     * @param array $options
     * @return mixed
     */
    public function getHelperProperties($media, string $format, array $options = []);

    /**
     * Generate the media path.
     *
     * @param array|MediaInterface $media
     *
     * @return string
     */
    public function generatePath($media);

    /**
     * Generate the public path.
     *
     * @param array|MediaInterface $media
     * @param string $format
     *
     * @return string
     */
    public function generatePublicUrl($media, string $format);

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
    public function generateSecuredUrl($media, string $format, string $identifier, int $expires = 300): string;

    /**
     * Generate the private path.
     *
     * @param MediaInterface $media
     * @param string $format
     *
     * @return string
     */
    public function generatePrivateUrl(MediaInterface $media, string $format);

    /**
     * @return array
     */
    public function getFormats();

    /**
     * @param string $name
     */
    public function setName(string $name);

    /**
     * @return string
     */
    public function getName();

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
    public function getResizer();

    /**
     * @return mixed
     */
    public function getFilesystem();

    /**
     * @param string $relativePath
     */
    public function getCdnPath($relativePath);

    /**
     * @param MediaInterface $media
     */
    public function transform(MediaInterface $media);

    /**
     * @param FormBuilderInterface $formBuilder
     * @param array $options
     * @return mixed
     */
    public function buildMediaType(FormBuilderInterface $formBuilder, array $options = []);

    /**
     * @param MediaInterface $media
     * @param bool $force
     */
    public function updateMetadata(MediaInterface $media, bool $force = false);
}
