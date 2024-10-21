<?php

namespace NetBull\MediaBundle\Provider;

use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Security\SecurityStrategyInterface;
use RuntimeException;

class Pool
{
    /**
     * @var array
     */
    protected array $providers = [];

    /**
     * @var array
     */
    protected array $contexts = [];

    /**
     * @var array
     */
    protected array $downloadSecurities = [];

    /**
     * @var array
     */
    protected array $viewSecurities = [];

    /**
     * @var string
     */
    protected string $defaultContext;

    /**
     * @param string $context
     */
    public function __construct(string $context)
    {
        $this->defaultContext = $context;
    }

    /**
     * @param string $name
     *
     * @return MediaProviderInterface
     * @throws RuntimeException
     *
     */
    public function getProvider(string $name): MediaProviderInterface
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
    public function addProvider(string $name, MediaProviderInterface $instance): void
    {
        $this->providers[$name] = $instance;
    }

    /**
     * @param string $name
     * @param SecurityStrategyInterface $security
     */
    public function addDownloadSecurity(string $name, SecurityStrategyInterface $security): void
    {
        $this->downloadSecurities[$name] = $security;
    }

    /**
     * @param string $name
     * @param SecurityStrategyInterface $security
     */
    public function addViewSecurity(string $name, SecurityStrategyInterface $security): void
    {
        $this->viewSecurities[$name] = $security;
    }

    /**
     * @param array $providers
     */
    public function setProviders(array $providers): void
    {
        $this->providers = $providers;
    }

    /**
     * @return MediaProviderInterface[]
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * @param string $name
     * @param array $providers
     * @param array $formats
     * @param array $download
     * @param array $view
     * @return void
     */
    public function addContext(string $name, array $providers = [], array $formats = [], array $download = [], array $view = []): void
    {
        if (!$this->hasContext($name)) {
            $this->contexts[$name] = [
                'providers' => [],
                'formats' => [],
                'download' => [],
                'view' => [],
            ];
        }

        $this->contexts[$name]['providers'] = $providers;
        $this->contexts[$name]['formats'] = $formats;
        $this->contexts[$name]['download'] = $download;
        $this->contexts[$name]['view'] = $view;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasContext(string $name): bool
    {
        return isset($this->contexts[$name]);
    }

    /**
     * @param string $name
     *
     * @return array|null
     */
    public function getContext(string $name): ?array
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
    public function getContexts(): array
    {
        return $this->contexts;
    }

    /**
     * @param string $name
     *
     * @return array|null
     */
    public function getProviderNamesByContext(string $name): ?array
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
    public function getFormatNamesByContext(string $name): ?array
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
    public function getProvidersByContext(string $name): array
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
    public function getProviderList(): array
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
     * @return SecurityStrategyInterface
     *
     * @throws RuntimeException
     */
    public function getDownloadSecurity(MediaInterface $media): SecurityStrategyInterface
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
     * @return SecurityStrategyInterface
     *
     * @throws RuntimeException
     */
    public function getViewSecurity(MediaInterface $media): SecurityStrategyInterface
    {
        $context = $this->getContext($media->getContext());

        $id = $context['view']['strategy'];

        if (!isset($this->viewSecurities[$id])) {
            throw new RuntimeException('Unable to retrieve the view security : '.$id);
        }

        return $this->viewSecurities[$id];
    }

    /**
     * @param MediaInterface $media
     *
     * @return string
     */
    public function getDownloadMode(MediaInterface $media): string
    {
        $context = $this->getContext($media->getContext());

        return $context['download']['mode'];
    }

    /**
     * @return string
     */
    public function getDefaultContext(): string
    {
        return $this->defaultContext;
    }
}
