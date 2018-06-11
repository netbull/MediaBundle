<?php

namespace NetBull\MediaBundle\Provider;

use Gaufrette\Filesystem;

use NetBull\MediaBundle\CDN\CDNInterface;
use NetBull\MediaBundle\Model\MediaInterface;
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
     * @var CDNInterface
     */
    protected $cdn;

    /**
     * BaseProvider constructor.
     * @param                       $name
     * @param Filesystem            $filesystem
     * @param CDNInterface          $cdn
     * @param ThumbnailInterface    $thumbnail
     */
    public function __construct($name, Filesystem $filesystem, CDNInterface $cdn, ThumbnailInterface $thumbnail)
    {
        $this->name         = $name;
        $this->filesystem   = $filesystem;
        $this->cdn          = $cdn;
        $this->thumbnail    = $thumbnail;
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

        if ($this->getFilesystem()->has($path)) {
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
     * {@inheritdoc}
     */
    public function getCdn()
    {
        return $this->cdn;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function setTemplates(array $templates)
    {
        $this->templates = $templates;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * {@inheritdoc}
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
