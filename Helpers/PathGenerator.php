<?php

namespace NetBull\MediaBundle\Helpers;

use NetBull\MediaBundle\Provider\Pool;
use NetBull\MediaBundle\Model\MediaInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class PathGenerator
 * @package NetBull\MediaBundle\Helpers
 */
class PathGenerator
{
    const FIRST_LEVEL = 100000;
    const SECOND_LEVEL = 1000;

    /**
     * @var Pool
     */
    private $pool;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * PathGenerator constructor.
     * @param Pool $pool
     * @param null|Environment $twig
     */
    function __construct(Pool $pool, ?Environment $twig)
    {
        $this->pool = $pool;
        $this->twig = $twig;
    }

    /**
     * @param $media
     * @return mixed
     */
    public static function generatePath($media)
    {
        if ($media instanceof MediaInterface) {
            $id = $media->getId();
            $context = $media->getContext();
        } else {
            $id = $media['id'];
            $context = $media['context'];
        }
        $rep_first_level = (int) ($id / self::FIRST_LEVEL);
        $rep_second_level = (int) (($id - ($rep_first_level * self::FIRST_LEVEL)) / self::SECOND_LEVEL);

        return sprintf('%s/%04s/%02s', $context, $rep_first_level + 1, $rep_second_level + 1);
    }

    /**
     * @param  MediaInterface|array $media$media
     * @param string                $format
     * @return string
     */
    public function generate($media, $format = 'normal')
    {
        $providerName = ($media instanceof MediaInterface)?$media->getProviderName():$media['providerName'];

        $provider = $this->pool->getProvider($providerName);
        return $provider->generatePublicUrl($media, $provider->getFormatName($media, $format));
    }

    /**
     * @param $media
     * @param string $format
     * @return null|string
     */
    public function view($media, $format = 'normal')
    {
        if (!$this->twig) {
            return null;
        }

        $providerName = ($media instanceof MediaInterface)?$media->getProviderName():$media['providerName'];

        $provider = $this->pool->getProvider($providerName);

        $format = $provider->getFormatName($media, $format);
        $format_definition = $provider->getFormat($format);
        if ($format_definition['width']) {
            $defaultOptions['width'] = $format_definition['width'];
        }
        if ($format_definition['height']) {
            $defaultOptions['height'] = $format_definition['height'];
        }

        $options = $provider->getViewProperties($media, $format, []);

        try {
            return $this->twig->render(
                $provider->getTemplate('helper_view'),
                [
                    'media' => $media,
                    'options' => $options,
                ]
            );
        } catch (LoaderError | RuntimeError | SyntaxError $e) {}

        return null;
    }
}
