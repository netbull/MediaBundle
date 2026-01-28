<?php

namespace NetBull\MediaBundle\Provider;

use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Security\SecurityStrategyInterface;

interface PoolInterface
{
    /**
     * @param string $name
     * @return MediaProviderInterface
     */
    public function getProvider(string $name): MediaProviderInterface;

    /**
     * @param string $name
     * @param MediaProviderInterface $instance
     */
    public function addProvider(string $name, MediaProviderInterface $instance): void;

    /**
     * @param string $name
     * @param SecurityStrategyInterface $security
     */
    public function addDownloadSecurity(string $name, SecurityStrategyInterface $security): void;

    /**
     * @param string $name
     * @param SecurityStrategyInterface $security
     */
    public function addViewSecurity(string $name, SecurityStrategyInterface $security): void;

    /**
     * @param array $providers
     */
    public function setProviders(array $providers): void;

    /**
     * @return MediaProviderInterface[]
     */
    public function getProviders(): array;

    /**
     * @param string $name
     * @param array $providers
     * @param array $formats
     * @param array $download
     * @param array $view
     * @return void
     */
    public function addContext(string $name, array $providers = [], array $formats = [], array $download = [], array $view = []): void;

    /**
     * @param string $name
     * @return bool
     */
    public function hasContext(string $name): bool;

    /**
     * @param string $name
     * @return array|null
     */
    public function getContext(string $name): ?array;

    /**
     * @return array
     */
    public function getContexts(): array;

    /**
     * @param string $name
     * @return array|null
     */
    public function getProviderNamesByContext(string $name): ?array;

    /**
     * @param string $name
     * @return array|null
     */
    public function getFormatNamesByContext(string $name): ?array;

    /**
     * @param string $name
     * @return array
     */
    public function getProvidersByContext(string $name): array;

    /**
     * @return array
     */
    public function getProviderList(): array;

    /**
     * @param MediaInterface $media
     * @return SecurityStrategyInterface
     */
    public function getDownloadSecurity(MediaInterface $media): SecurityStrategyInterface;

    /**
     * @param MediaInterface $media
     * @return SecurityStrategyInterface
     */
    public function getViewSecurity(MediaInterface $media): SecurityStrategyInterface;

    /**
     * @param MediaInterface $media
     * @return string
     */
    public function getDownloadMode(MediaInterface $media): string;

    /**
     * @return string
     */
    public function getDefaultContext(): string;
}
