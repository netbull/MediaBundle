<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Provider;

use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Security\SecurityStrategyInterface;

interface PoolInterface
{
    public function getProvider(string $name): MediaProviderInterface;

    public function addProvider(string $name, MediaProviderInterface $instance): void;

    public function addDownloadSecurity(string $name, SecurityStrategyInterface $security): void;

    public function addViewSecurity(string $name, SecurityStrategyInterface $security): void;

    public function setProviders(array $providers): void;

    /**
     * @return MediaProviderInterface[]
     */
    public function getProviders(): array;

    public function addContext(string $name, array $providers = [], array $formats = [], array $download = [], array $view = []): void;

    public function hasContext(string $name): bool;

    public function getContext(string $name): ?array;

    public function getContexts(): array;

    public function getProviderNamesByContext(string $name): ?array;

    public function getFormatNamesByContext(string $name): ?array;

    public function getProvidersByContext(string $name): array;

    public function getProviderList(): array;

    public function getDownloadSecurity(MediaInterface $media): SecurityStrategyInterface;

    public function getViewSecurity(MediaInterface $media): SecurityStrategyInterface;

    public function getDownloadMode(MediaInterface $media): string;

    public function getDefaultContext(): string;
}
