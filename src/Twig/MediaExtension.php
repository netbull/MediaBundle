<?php

namespace NetBull\MediaBundle\Twig;

use Doctrine\ORM\EntityManagerInterface;
use NetBull\MediaBundle\Provider\Pool;
use NetBull\MediaBundle\Entity\MediaInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MediaExtension extends AbstractExtension
{
    /**
     * @var Pool
     */
    private Pool $pool;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $em;

    /**
     * @var array
     */
    protected array $resources = [];

    /**
     * @param Pool $pool
     * @param EntityManagerInterface $em
     */
    public function __construct(Pool $pool, EntityManagerInterface $em)
    {
        $this->pool = $pool;
        $this->em = $em;
    }

    /**
     * @inheritdoc
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('path', [$this, 'generatePublicPath']),
            new TwigFilter('secure_path', [$this, 'generateSecurePath']),
            new TwigFilter('thumbnail', [$this, 'generateThumbnail'], ['is_safe' => ['html'], 'needs_environment' => true]),
            new TwigFilter('view', [$this, 'generateView'], ['is_safe' => ['html'], 'needs_environment' => true]),
        ];
    }

    /**
     * @param array|MediaInterface $media
     * @param string $format
     * @return string
     */
    public function generatePublicPath(array|MediaInterface $media, string $format = 'normal'): string
    {
        if ($media instanceof MediaInterface) {
            $providerName = $media->getProviderName();
        } else {
            if (isset($media['providerName'])) {
                $providerName = $media['providerName'];
            } else {
                return '';
            }
        }

        $provider = $this->pool->getProvider($providerName);

        return $provider->generatePublicUrl($media, $provider->getFormatName($media, $format));
    }

    /**
     * @param array|MediaInterface $media
     * @param string $identifier
     * @param string $format
     * @return string
     */
    public function generateSecurePath(array|MediaInterface $media, string $identifier, string $format = 'normal'): string
    {
        if ($media instanceof MediaInterface) {
            $providerName = $media->getProviderName();
        } else {
            if (isset($media['providerName'])) {
                $providerName = $media['providerName'];
            } else {
                return '';
            }
        }

        $provider = $this->pool->getProvider($providerName);

        return $provider->generateSecuredUrl($media, $provider->getFormatName($media, $format), $identifier);
    }

    /**
     * @param Environment $environment
     * @param array|MediaInterface $media
     * @param string $format
     * @param array $options
     * @return string
     */
    public function generateThumbnail(Environment $environment, array|MediaInterface $media, string $format, array $options = []): string
    {
        return $this->generateTemplate($environment, $media, $format, 'thumbnail', $options);
    }

    /**
     * @param Environment $environment
     * @param array|MediaInterface $media
     * @param string $format
     * @param array $options
     * @return string
     */
    public function generateView(Environment $environment, array|MediaInterface $media, string $format, array $options = []): string
    {
        return $this->generateTemplate($environment, $media, $format, 'view', $options);
    }

    /**
     * @param Environment $environment
     * @param array|MediaInterface $media
     * @param string $format
     * @param string $template
     * @param array $options
     * @return string
     */
    private function generateTemplate(Environment $environment, array|MediaInterface $media, string $format, string $template, array $options = []): string
    {
        if ($media instanceof MediaInterface) {
            $providerName = $media->getProviderName();
        } else {
            if (isset($media['providerName'])) {
                $providerName = $media['providerName'];
            } else {
                return '';
            }
        }
        $defaultOptions = [];

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

        return $this->render(
            $environment,
            $provider->getTemplate('helper_'.$template),
            [
                'media' => $media,
                'options' => $options,
            ]
        );
    }

    /**
     * @param Environment $environment
     * @param string $template
     * @param array $parameters
     * @return string
     */
    public function render(Environment $environment, string $template, array $parameters = []): string
    {
        if (!isset($this->resources[$template])) {
            try {
                $this->resources[$template] = $environment->load("@$template");
            } catch (LoaderError | RuntimeError | SyntaxError $e) {
                return '';
            }
        }

        return $this->resources[$template]->render($parameters);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'netbull_media.extension';
    }
}
