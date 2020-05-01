<?php

namespace NetBull\MediaBundle\DependencyInjection\Compiler;

use NetBull\MediaBundle\DependencyInjection\Configuration;
use NetBull\MediaBundle\DependencyInjection\NetBullMediaExtension;
use NetBull\MediaBundle\Provider\Pool;
use Symfony\Component\Config\Definition\Processor;
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
     * @var array
     */
    private $config = [];

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->getConfiguration($container);

        // define configuration per provider
        $this->applyFormats($container);
        $this->attachArguments($container);
        $this->attachProviders($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    public function attachProviders(ContainerBuilder $container)
    {
        $pool = $container->getDefinition(Pool::class);
        foreach ($container->findTaggedServiceIds('netbull_media.provider') as $id => $attributes) {
            $pool->addMethodCall('addProvider', [$id, new Reference($id)]);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function getConfiguration(ContainerBuilder $container)
    {
        $parameterBag = $container->getParameterBag();
        $processor = new Processor();
        $configuration = new Configuration();
        $resolvedConfig = $parameterBag->resolveValue($container->getExtensionConfig('netbull_media'));

        $this->config = $processor->processConfiguration($configuration, $resolvedConfig);
    }

    /**
     * @param ContainerBuilder $container
     */
    public function attachArguments(ContainerBuilder $container)
    {
        if (!isset($this->config['providers'])) {
            return;
        }

        foreach ($container->findTaggedServiceIds('netbull_media.provider') as $id => $attributes) {
            foreach ($this->config['providers'] as $name => $config) {
                if ($config['service'] === $id) {
                    $definition = $container->getDefinition($id);

                    $definition
                        ->replaceArgument(1, new Reference($config['filesystem']))
                        ->replaceArgument(2, new Reference($config['cdn']))
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
     * @param ContainerBuilder $container
     */
    public function applyFormats(ContainerBuilder $container)
    {
        if (!isset($this->config['contexts'])) {
            return;
        }

        foreach ($this->config['contexts'] as $name => $context) {
            // add the different related formats
            foreach ($context['providers'] as $id) {
                $definition = $container->getDefinition($id);

                foreach ($context['formats'] as $format => $config) {
                    $config['quality'] = isset($config['quality']) ? $config['quality'] : 80;
                    $config['format'] = isset($config['format']) ? $config['format'] : 'jpg';
                    $config['height'] = isset($config['height']) ? $config['height'] : null;
                    $config['constraint'] = isset($config['constraint']) ? $config['constraint'] : true;

                    $formatName = sprintf('%s_%s', $name, $format);
                    $definition->addMethodCall('addFormat', [$formatName, $config]);
                }
            }
        }
    }
}
