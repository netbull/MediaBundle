<?php

namespace NetBull\MediaBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use NetBull\MediaBundle\DependencyInjection\NetBullMediaExtension;
use NetBull\MediaBundle\DependencyInjection\Compiler\AddProviderCompilerPass;

/**
 * Class NetBullMediaBundle
 * @package NetBull\MediaBundle
 */
class NetBullMediaBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddProviderCompilerPass());
    }

    /**
     * @return NetBullMediaExtension|null|\Symfony\Component\DependencyInjection\Extension\ExtensionInterface
     */
    public function getContainerExtension()
    {
        return new NetBullMediaExtension();
    }
}
