<?php

namespace NetBull\MediaBundle\Provider;

use Gaufrette\Filesystem;
use NetBull\MediaBundle\Cdn\CdnInterface;
use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Helpers\PathGenerator;
use NetBull\MediaBundle\Resizer\ResizerInterface;
use NetBull\MediaBundle\Thumbnail\ThumbnailInterface;

/**
 * Class BaseProvider
 * @package NetBull\MediaBundle\Provider
 */
abstract class BaseProvider implements MediaProviderInterface
{
    /**
     * @var array
     */
    protected $formats = [];

    /**
     * @var array
     */
    protected $templates = [];

    /**
     * @var
     */
    protected $resizer;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var ThumbnailInterface
     */
    protected $thumbnail;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var CdnInterface
     */
    protected $cdn;

    /**
     * BaseProvider constructor.
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
     * @return mixed
     */
    abstract protected function doTransform(MediaInterface $media);

    /**
     * @param MediaInterface $media
     */
    final public function transform(MediaInterface $media)
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
    public function addFormat($name, $format)
    {
        $this->formats[$name] = $format;
    }

    /**
     * @param $name
     * @return bool|mixed
     */
    public function getFormat($name)
    {
        return isset($this->formats[$name]) ? $this->formats[$name] : false;
    }

    /**
     * @return bool
     */
    public function requireThumbnails()
    {
        return $this->getResizer() !== null;
    }

    /**
     * @param MediaInterface $media
     */
    public function generateThumbnails(MediaInterface $media)
    {
        $this->thumbnail->generate($this, $media);
    }

    /**
     * @param MediaInterface    $media
     * @param string            $format
     */
    public function generateThumbnail(MediaInterface $media, $format)
    {
        $this->thumbnail->generateByFormat($this, $media, $format);
    }

    /**
     * @param MediaInterface $media
     */
    public function removeThumbnails(MediaInterface $media)
    {
        $this->thumbnail->delete($this, $media);
    }

    /**
     * @param MediaInterface|array  $media
     * @param                       $format
     * @return string
     */
    public function getFormatName($media, $format)
    {
        if ('reference' === $format) {
            return 'reference';
        }

        $context = ($media instanceof MediaInterface) ? $media->getContext() : $media['context'];
        $baseName = $context . '_';
        if (substr($format, 0, strlen($baseName)) === $baseName) {
            return $format;
        }

        return $baseName.$format;
    }

    /**
     * @param MediaInterface $media
     */
    public function preRemove(MediaInterface $media)
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
     */
    public function postRemove(MediaInterface $media){ }

    /**
     * @inheritdoc
     */
    public function postFlush(MediaInterface $media){ }

    /**
     * @param array|MediaInterface $media
     * @return mixed
     */
    public function generatePath($media)
    {
        return PathGenerator::generatePath($media);
    }

    /**
     * @return array
     */
    public function getFormats()
    {
        return $this->formats;
    }

    /**
     * @param $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getResizer()
    {
        return $this->resizer;
    }

    /**
     * @return Filesystem
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }

    /**
     * @return CdnInterface
     */
    public function getCdn()
    {
        return $this->cdn;
    }

    /**
     * @param string $relativePath
     * @return string
     */
    public function getCdnPath($relativePath)
    {
        return $this->getCdn()->getPath($relativePath);
    }

    /**
     * @param ResizerInterface $resizer
     */
    public function setResizer(ResizerInterface $resizer)
    {
        $this->resizer = $resizer;
    }

    /**
     * @param MediaInterface $media
     */
    public function prePersist(MediaInterface $media) { }

    /**
     * @param MediaInterface $media
     */
    public function preUpdate(MediaInterface $media) { }

    /**
     * @param array $templates
     */
    public function setTemplates(array $templates)
    {
        $this->templates = $templates;
    }

    /**
     * @return array
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function getTemplate($name)
    {
        return isset($this->templates[$name]) ? $this->templates[$name] : null;
    }

    /**
     * @param $media
     * @param $format
     * @param array $options
     * @return mixed
     */
    public function getViewProperties($media, $format, array $options = [])
    {
        return $this->getHelperProperties($media, $format, $options);
    }
}
