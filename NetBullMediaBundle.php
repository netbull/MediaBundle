<?php

namespace NetBull\MediaBundle;

use Symfony\Component\Console\Application;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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

    public function registerCommands(Application $application)
    {
        // noop
    }
}
