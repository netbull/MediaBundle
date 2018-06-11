<?php

namespace NetBull\MediaBundle\Twig;

use Doctrine\ORM\EntityManager;

use NetBull\MediaBundle\Provider\Pool;
use NetBull\MediaBundle\Model\MediaInterface;

/**
 * Class MediaExtension
 * @package NetBull\MediaBundle\Twig
 */
class MediaExtension extends \Twig_Extension
{
    /**
     * @var Pool
     */
    private $pool;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var array
     */
    protected $resources = [];

    /**
     * MediaExtension constructor.
     * @param Pool $pool
     * @param EntityManager $em
     */
    public function __construct(Pool $pool, EntityManager $em)
    {
        $this->pool = $pool;
        $this->em   = $em;
    }

    /**
     * @inheritdoc
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('path', [$this, 'generatePublicPath']),
            new \Twig_SimpleFilter('thumbnail', [$this, 'generateThumbnail'], ['is_safe' => ['html'], 'needs_environment' => true]),
            new \Twig_SimpleFilter('view', [$this, 'generateView'], ['is_safe' => ['html'], 'needs_environment' => true]),
        ];
    }

    /**
     * @param array|MediaInterface  $media
     * @param string                $format
     * @return string
     */
    public function generatePublicPath($media, $format = 'normal')
    {
        if ($media instanceof MediaInterface) {
            $providerName = $media->getProviderName();
        } else if(isset($media['providerName'])) {
            $providerName = $media['providerName'];
        } else {
            return '';
        }

        $provider = $this->pool->getProvider($providerName);

        return $provider->generatePublicUrl($media, $provider->getFormatName($media, $format));
    }

    /**
     * @param \Twig_Environment $environment
     * @param                   $media
     * @param                   $format
     * @param array             $options
     * @return mixed|string
     */
    public function generateThumbnail(\Twig_Environment $environment, $media, $format, $options = [])
    {
        return $this->generateTemplate($environment, $media, $format, $options, 'thumbnail');
    }

    /**
     * @param \Twig_Environment $environment
     * @param                   $media
     * @param                   $format
     * @param array             $options
     * @return mixed|string
     */
    public function generateView(\Twig_Environment $environment, $media, $format, $options = [])
    {
        return $this->generateTemplate($environment, $media, $format, $options, 'view');
    }

    /**
     * @param \Twig_Environment $environment
     * @param                   $media
     * @param                   $format
     * @param array             $options
     * @param string            $template
     * @return string
     */
    private function generateTemplate(\Twig_Environment $environment, $media, $format, $options = [], $template)
    {
        if ($media instanceof MediaInterface) {
            $providerName = $media->getProviderName();
        } else if(isset($media['providerName'])) {
            $providerName = $media['providerName'];
        } else {
            return '';
        }
        $defaultOptions= [];

        $provider = $this->pool->getProvider($providerName);
        $format = $provider->getFormatName($media, $format);
        $format_definition = $provider->getFormat($format);

        if ($format_definition['width']) {
            $defaultOptions['width'] = $format_definition['width'];
        }

        if ($format_definition['height']) {
            $defaultOptions['height'] = $format_definition['height'];
        }

        $options = array_merge($defaultOptions, $options);

        if ('view' === $template) {
            $options = $provider->getViewProperties($media, $format, $options);
        } else {
            $options = $provider->getHelperProperties($media, $format, $options);
        }

        return $this->render($environment, $provider->getTemplate('helper_' . $template), [
            'media'    => $media,
            'options'  => $options,
        ]);
    }

    /**
     * @param \Twig_Environment $environment
     * @param $template
     * @param array $parameters
     * @return mixed
     */
    public function render(\Twig_Environment $environment, $template, array $parameters = [])
    {
        if (!isset($this->resources[$template])) {
            try {
                $this->resources[$template] = $environment->loadTemplate($template);
            } catch (\Twig_Error_Loader | \Twig_Error_Runtime | \Twig_Error_Syntax $e) {}
        }

        return $this->resources[$template]->render($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'netbull_media.extension';
    }
}
