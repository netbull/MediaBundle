<?php

namespace NetBull\MediaBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Class AddProviderCompilerPass
 * @package NetBull\MediaBundle\DependencyInjection\Compiler
 */
class AddProviderCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $settings = $this->fixSettings($container);

        // define configuration per provider
        $this->applyFormats($container, $settings);
        $this->attachArguments($container, $settings);
        $this->attachProviders($container);
    }

    /**
     * @param ContainerBuilder $container
     * @return bool|array
     */
    public function fixSettings(ContainerBuilder $container)
    {
        $pool = $container->getDefinition('netbull_media.pool');

        // not very clean but don't know how to do that for now
        $settings = false;
        $methods  = $pool->getMethodCalls();
        foreach ($methods as $pos => $calls) {
            if ($calls[0] === '__hack__') {
                $settings = $calls[1];
                break;
            }
        }

        if ($settings) {
            unset($methods[$pos]);
        }

        $pool->setMethodCalls($methods);

        return $settings;
    }

    /**
     * @param ContainerBuilder $container
     */
    public function attachProviders(ContainerBuilder $container)
    {
        $pool = $container->getDefinition('netbull_media.pool');
        foreach ($container->findTaggedServiceIds('netbull_media.provider') as $id => $attributes) {
            $pool->addMethodCall('addProvider', [$id, new Reference($id)]);
        }
    }

    /**
     * @param ContainerBuilder  $container
     * @param array             $settings
     */
    public function attachArguments(ContainerBuilder $container, array $settings)
    {
        foreach ($container->findTaggedServiceIds('netbull_media.provider') as $id => $attributes) {
            foreach ($settings['providers'] as $name => $config) {
                if ($config['service'] === $id) {
                    $definition = $container->getDefinition($id);

                    $definition
                        ->replaceArgument(1, new Reference($config['filesystem']))
                        ->replaceArgument(2, new Reference($config['cdn']))
                        ->replaceArgument(3, new Reference($config['thumbnail']))
                    ;

                    if ($config['resizer']) {
                        $definition->addMethodCall('setResizer', [new Reference($config['resizer'])]);
                    }
                }
            }
        }
    }

    /**
     * Define the default settings to the config array.
     * @param ContainerBuilder  $container
     * @param array             $settings
     */
    public function applyFormats(ContainerBuilder $container, array $settings)
    {
        foreach ($settings['contexts'] as $name => $context) {
            // add the different related formats
            foreach ($context['providers'] as $id) {
                $definition = $container->getDefinition($id);

                foreach ($context['formats'] as $format => $config) {
                    $config['quality']      = isset($config['quality']) ? $config['quality'] : 80;
                    $config['format']       = isset($config['format'])  ? $config['format'] : 'jpg';
                    $config['height']       = isset($config['height'])  ? $config['height'] : null;
                    $config['constraint']   = isset($config['constraint'])  ? $config['constraint'] : true;

                    $formatName = sprintf('%s_%s', $name, $format);
                    $definition->addMethodCall('addFormat', [$formatName, $config]);
                }
            }
        }
    }
}
