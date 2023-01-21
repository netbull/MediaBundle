<?php

namespace NetBull\MediaBundle;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use NetBull\MediaBundle\DependencyInjection\NetBullMediaExtension;
use NetBull\MediaBundle\DependencyInjection\Compiler\AddProviderCompilerPass;

class NetBullMediaBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AddProviderCompilerPass());
    }

    /**
     * @return ExtensionInterface|null
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new NetBullMediaExtension();
    }
}
