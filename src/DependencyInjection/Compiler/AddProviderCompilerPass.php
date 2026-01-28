<?php

namespace NetBull\MediaBundle\DependencyInjection\Compiler;

use NetBull\MediaBundle\DependencyInjection\Configuration;
use NetBull\MediaBundle\Provider\Pool;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class AddProviderCompilerPass implements CompilerPassInterface
{
    /**
     * @var array
     */
    private array $config = [];

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container): void
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
    public function attachProviders(ContainerBuilder $container): void
    {
        $pool = $container->getDefinition(Pool::class);
        foreach ($container->findTaggedServiceIds('netbull_media.provider') as $id => $attributes) {
            $pool->addMethodCall('addProvider', [$id, new Reference($id)]);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function getConfiguration(ContainerBuilder $container): void
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
    public function attachArguments(ContainerBuilder $container): void
    {
        if (!isset($this->config['providers'])) {
            return;
        }

        foreach ($container->findTaggedServiceIds('netbull_media.provider') as $id => $attributes) {
            foreach ($this->config['providers'] as $name => $config) {
                if ($config['service'] === $id) {
                    $definition = $container->getDefinition($id);

                    // Fall back to local filesystem if the configured one doesn't exist
                    $filesystem = $config['filesystem'];
                    if (!$container->hasDefinition($filesystem) && $container->hasDefinition('netbull_media.filesystem.local')) {
                        $filesystem = 'netbull_media.filesystem.local';
                    }

                    $definition->replaceArgument(1, new Reference($filesystem))
                        ->replaceArgument(2, new Reference($config['cdn']));

                    if ($config['resizer']) {
                        $definition->addMethodCall('setResizer', [new Reference($config['resizer'])]);
                    }
                }
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    public function applyFormats(ContainerBuilder $container): void
    {
        if (!isset($this->config['contexts'])) {
            return;
        }

        foreach ($this->config['contexts'] as $name => $context) {
            // add the different related formats
            foreach ($context['providers'] as $id) {
                $definition = $container->getDefinition($id);

                foreach ($context['formats'] as $format => $config) {
                    $config['quality'] = $config['quality'] ?? 80;
                    $config['format'] = $config['format'] ?? 'jpg';
                    $config['height'] = $config['height'] ?? null;
                    $config['constraint'] = $config['constraint'] ?? true;

                    $formatName = sprintf('%s_%s', $name, $format);
                    $definition->addMethodCall('addFormat', [$formatName, $config]);
                }
            }
        }
    }
}
