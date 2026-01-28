<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Provider;

use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Security\SecurityStrategyInterface;
use RuntimeException;

class Pool implements PoolInterface
{
    protected array $providers = [];

    protected array $contexts = [];

    protected array $downloadSecurities = [];

    protected array $viewSecurities = [];

    protected string $defaultContext;

    public function __construct(string $context)
    {
        $this->defaultContext = $context;
    }

    /**
     * @throws RuntimeException
     */
    public function getProvider(string $name): MediaProviderInterface
    {
        if (!isset($this->providers[$name])) {
            throw new RuntimeException(\sprintf('unable to retrieve the provider named : `%s`', $name));
        }

        return $this->providers[$name];
    }

    public function addProvider(string $name, MediaProviderInterface $instance): void
    {
        $this->providers[$name] = $instance;
    }

    public function addDownloadSecurity(string $name, SecurityStrategyInterface $security): void
    {
        $this->downloadSecurities[$name] = $security;
    }

    public function addViewSecurity(string $name, SecurityStrategyInterface $security): void
    {
        $this->viewSecurities[$name] = $security;
    }

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

    public function hasContext(string $name): bool
    {
        return isset($this->contexts[$name]);
    }

    public function getContext(string $name): ?array
    {
        if (!$this->hasContext($name)) {
            return null;
        }

        return $this->contexts[$name];
    }

    /**
     * Returns the context list.
     */
    public function getContexts(): array
    {
        return $this->contexts;
    }

    public function getProviderNamesByContext(string $name): ?array
    {
        $context = $this->getContext($name);

        if (!$context) {
            return null;
        }

        return $context['providers'];
    }

    public function getFormatNamesByContext(string $name): ?array
    {
        $context = $this->getContext($name);

        if (!$context) {
            return null;
        }

        return $context['formats'];
    }

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

    public function getProviderList(): array
    {
        $choices = [];
        foreach (array_keys($this->providers) as $name) {
            $choices[$name] = $name;
        }

        return $choices;
    }

    /**
     * @throws RuntimeException
     */
    public function getDownloadSecurity(MediaInterface $media): SecurityStrategyInterface
    {
        $context = $this->getContext($media->getContext());

        $id = $context['download']['strategy'];

        if (!isset($this->downloadSecurities[$id])) {
            throw new RuntimeException('Unable to retrieve the download security : ' . $id);
        }

        return $this->downloadSecurities[$id];
    }

    /**
     * @throws RuntimeException
     */
    public function getViewSecurity(MediaInterface $media): SecurityStrategyInterface
    {
        $context = $this->getContext($media->getContext());

        $id = $context['view']['strategy'];

        if (!isset($this->viewSecurities[$id])) {
            throw new RuntimeException('Unable to retrieve the view security : ' . $id);
        }

        return $this->viewSecurities[$id];
    }

    public function getDownloadMode(MediaInterface $media): string
    {
        $context = $this->getContext($media->getContext());

        return $context['download']['mode'];
    }

    public function getDefaultContext(): string
    {
        return $this->defaultContext;
    }
}
