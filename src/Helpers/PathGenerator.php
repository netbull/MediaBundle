<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Helpers;

use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Provider\Pool;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class PathGenerator
{
    public const int FIRST_LEVEL = 100000;

    public const int SECOND_LEVEL = 1000;

    public function __construct(
        private Pool $pool,
        private ?Environment $twig,
    ) {
    }

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

        return \sprintf('%s/%04s/%02s', $context, $rep_first_level + 1, $rep_second_level + 1);
    }

    public function generate(array|MediaInterface $media, string $format = 'normal'): string
    {
        $providerName = $media instanceof MediaInterface ? $media->getProviderName() : $media['providerName'];

        $provider = $this->pool->getProvider($providerName);

        return $provider->generatePublicUrl($media, $provider->getFormatName($media, $format));
    }

    public function generateSecure(array|MediaInterface $media, string $identifier, string $format = 'normal'): string
    {
        $providerName = $media instanceof MediaInterface ? $media->getProviderName() : $media['providerName'];

        $provider = $this->pool->getProvider($providerName);

        return $provider->generateSecuredUrl($media, $format, $identifier);
    }

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
                ],
            );
        } catch (LoaderError|RuntimeError|SyntaxError) {
        }

        return null;
    }
}
