<?php

namespace NetBull\MediaBundle\Helpers;

use NetBull\MediaBundle\Provider\Pool;
use NetBull\MediaBundle\Entity\MediaInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

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
     * @param Pool $pool
     * @param Environment|null $twig
     */
    function __construct(Pool $pool, ?Environment $twig)
    {
        $this->pool = $pool;
        $this->twig = $twig;
    }

    /**
     * @param array|MediaInterface $media
     * @return string
     */
    public static function generatePath(array|MediaInterface $media): string
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
     * @param array|MediaInterface $media
     * @param string $format
     * @return string
     */
    public function generate(array|MediaInterface $media, string $format = 'normal'): string
    {
        $providerName = $media instanceof MediaInterface ? $media->getProviderName() : $media['providerName'];

        $provider = $this->pool->getProvider($providerName);
        return $provider->generatePublicUrl($media, $provider->getFormatName($media, $format));
    }

    /**
     * @param array|MediaInterface $media
     * @param string $identifier
     * @param string $format
     * @return string
     */
    public function generateSecure(array|MediaInterface $media, string $identifier, string $format = 'normal'): string
    {
        $providerName = $media instanceof MediaInterface ? $media->getProviderName() : $media['providerName'];

        $provider = $this->pool->getProvider($providerName);
        return $provider->generateSecuredUrl($media, $format, $identifier);
    }

    /**
     * @param $media
     * @param string $format
     * @return string|null
     */
    public function view($media, string $format = 'normal'): ?string
    {
        if (!$this->twig) {
            return null;
        }

        $providerName = $media instanceof MediaInterface ? $media->getProviderName() : $media['providerName'];

        $provider = $this->pool->getProvider($providerName);

        $format = $provider->getFormatName($media, $format);
        $options = $provider->getViewProperties($media, $format);

        try {
            return $this->twig->render(
                $provider->getTemplate('helper_view'),
                [
                    'media' => $media,
                    'options' => $options,
                ]
            );
        } catch (LoaderError | RuntimeError | SyntaxError) {}

        return null;
    }
}
