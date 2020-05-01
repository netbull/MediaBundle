<?php

namespace NetBull\MediaBundle\Provider;

use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Security\DownloadStrategyInterface;
use RuntimeException;

/**
 * Class Pool
 * @package NetBull\MediaBundle\Provider
 */
class Pool
{
    /**
     * @var array
     */
    protected $providers = [];

    /**
     * @var array
     */
    protected $contexts = [];

    /**
     * @var array
     */
    protected $downloadSecurities = [];

    /**
     * @var string
     */
    protected $defaultContext;

    /**
     * @param string $context
     */
    public function __construct(string $context)
    {
        $this->defaultContext = $context;
    }

    /**
     * @throws RuntimeException
     *
     * @param string $name
     *
     * @return MediaProviderInterface
     */
    public function getProvider($name)
    {
        if (!isset($this->providers[$name])) {
            throw new RuntimeException(sprintf('unable to retrieve the provider named : `%s`', $name));
        }

        return $this->providers[$name];
    }

    /**
     * @param string $name
     * @param MediaProviderInterface $instance
     */
    public function addProvider(string $name, MediaProviderInterface $instance)
    {
        $this->providers[$name] = $instance;
    }

    /**
     * @param string $name
     * @param DownloadStrategyInterface $security
     */
    public function addDownloadSecurity(string $name, DownloadStrategyInterface $security)
    {
        $this->downloadSecurities[$name] = $security;
    }

    /**
     * @param array $providers
     */
    public function setProviders(array $providers)
    {
        $this->providers = $providers;
    }

    /**
     * @return MediaProviderInterface[]
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * @param string $name
     * @param array $providers
     * @param array $formats
     * @param array $download
     */
    public function addContext(string $name, array $providers = [], array $formats = [], array $download = [])
    {
        if (!$this->hasContext($name)) {
            $this->contexts[$name] = [
                'providers' => [],
                'formats' => [],
                'download' => [],
            ];
        }

        $this->contexts[$name]['providers'] = $providers;
        $this->contexts[$name]['formats'] = $formats;
        $this->contexts[$name]['download'] = $download;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasContext(string $name)
    {
        return isset($this->contexts[$name]);
    }

    /**
     * @param string $name
     *
     * @return array|null
     */
    public function getContext(string $name)
    {
        if (!$this->hasContext($name)) {
            return null;
        }

        return $this->contexts[$name];
    }

    /**
     * Returns the context list.
     *
     * @return array
     */
    public function getContexts()
    {
        return $this->contexts;
    }

    /**
     * @param string $name
     *
     * @return array|null
     */
    public function getProviderNamesByContext(string $name)
    {
        $context = $this->getContext($name);

        if (!$context) {
            return null;
        }

        return $context['providers'];
    }

    /**
     * @param string $name
     *
     * @return array|null
     */
    public function getFormatNamesByContext(string $name)
    {
        $context = $this->getContext($name);

        if (!$context) {
            return null;
        }

        return $context['formats'];
    }

    /**
     * @param string $name
     *
     * @return array
     */
    public function getProvidersByContext(string $name)
    {
        $providers = [];

        if (!$this->hasContext($name)) {
            return $providers;
        }

        foreach ($this->getProviderNamesByContext($name) as $name) {
            $providers[] = $this->getProvider($name);
        }

        return $providers;
    }

    /**
     * @return array
     */
    public function getProviderList()
    {
        $choices = [];
        foreach (array_keys($this->providers) as $name) {
            $choices[$name] = $name;
        }

        return $choices;
    }

    /**
     * @param MediaInterface $media
     *
     * @return DownloadStrategyInterface
     *
     * @throws RuntimeException
     */
    public function getDownloadSecurity(MediaInterface $media)
    {
        $context = $this->getContext($media->getContext());

        $id = $context['download']['strategy'];

        if (!isset($this->downloadSecurities[$id])) {
            throw new RuntimeException('Unable to retrieve the download security : '.$id);
        }

        return $this->downloadSecurities[$id];
    }

    /**
     * @param MediaInterface $media
     *
     * @return string
     */
    public function getDownloadMode(MediaInterface $media)
    {
        $context = $this->getContext($media->getContext());

        return $context['download']['mode'];
    }

    /**
     * @return string
     */
    public function getDefaultContext()
    {
        return $this->defaultContext;
    }
}
