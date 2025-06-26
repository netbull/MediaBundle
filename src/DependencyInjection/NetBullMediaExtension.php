<?php

namespace NetBull\MediaBundle\DependencyInjection;

use Exception;
use Imagine\Image\ManipulatorInterface;
use NetBull\MediaBundle\Provider\Pool;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class NetBullMediaExtension extends Extension
{
    /**
     * @var array
     */
    private array $config = [];

    /**
     * @param array $configs
     * @param ContainerBuilder $container
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $this->config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('provider.yaml');
        $loader->load('listener.yaml');
        $loader->load('media.yaml');
        $loader->load('security.yaml');
        $loader->load('gaufrette.yaml');
        $loader->load('form.yaml');
        $loader->load('twig.yaml');
        $loader->load('helpers.yaml');
        $loader->load('console.yaml');
        $loader->load('signature.yaml');
        $loader->load('controller.yaml');

        $this->configureFilesystemAdapter($container);
        $this->configureCdnAdapter($container);

        $pool = $container->getDefinition(Pool::class);
        $pool->replaceArgument(0, $this->config['default_context']);

        $container->setParameter('netbull_media.resizer.simple.adapter.mode', $this->config['resizer']['simple']['mode'] === 'outbound' ? ManipulatorInterface::THUMBNAIL_OUTBOUND : ManipulatorInterface::THUMBNAIL_INSET);
        $container->setParameter('netbull_media.resizer.square.adapter.mode', $this->config['resizer']['square']['mode'] === 'outbound' ? ManipulatorInterface::THUMBNAIL_OUTBOUND : ManipulatorInterface::THUMBNAIL_INSET);

        foreach (['netbull_media.resizer.simple', 'netbull_media.resizer.square'] as $resizerId) {
            if ($container->hasDefinition($resizerId)) {
                $container->getDefinition($resizerId)
                    ->replaceArgument(0, new Reference('netbull_media.adapter.image.'.$this->config['resizer']['adapter']));
            }
        }

        $downloadStrategies = $viewStrategies = [];
        foreach ($this->config['contexts'] as $name => $settings) {
            $formats = [];
            foreach ($settings['formats'] as $format => $value) {
                $formats[$name.'_'.$format] = $value;
            }

            $downloadStrategies[] = $settings['download']['strategy'];
            $viewStrategies[] = $settings['view']['strategy'];
            $pool->addMethodCall('addContext', [$name, $settings['providers'], $formats, $settings['download'], $settings['view']]);
        }

        $downloadStrategies = array_unique($downloadStrategies);
        foreach ($downloadStrategies as $strategyId) {
            $pool->addMethodCall('addDownloadSecurity', [$strategyId, new Reference($strategyId)]);
        }
        $viewStrategies = array_unique($viewStrategies);
        foreach ($viewStrategies as $strategyId) {
            $pool->addMethodCall('addViewSecurity', [$strategyId, new Reference($strategyId)]);
        }

        $this->configureProviders($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    public function configureFilesystemAdapter(ContainerBuilder $container): void
    {
        // Add the default configuration for the local filesystem
        if ($container->hasDefinition('netbull_media.adapter.filesystem.local') && isset($this->config['filesystem']['local'])) {
            $container->getDefinition('netbull_media.adapter.filesystem.local')
                ->replaceArgument(0, $this->config['filesystem']['local']['directory'])
                ->addArgument($this->config['filesystem']['local']['create']);
        } else {
            $container->removeDefinition('netbull_media.adapter.filesystem.local');
        }

        // Add the default configuration for the S3 filesystem
        if ($container->hasDefinition('netbull_media.adapter.filesystem.s3') && isset($this->config['filesystem']['s3'])) {
            $options = [
                'version' => $this->config['filesystem']['s3']['defaults']['version'],
                'region' => $this->config['filesystem']['s3']['defaults']['region'],
            ];
            if (!empty($this->config['filesystem']['s3']['defaults']['credentials'])) {
                $options['credentials'] = $this->config['filesystem']['s3']['defaults']['credentials'];
            }
            $container->getDefinition('netbull_media.wrapper.s3')
                ->replaceArgument(0, $options);

            $container->getDefinition('netbull_media.adapter.filesystem.s3')
                ->replaceArgument(1, $this->config['filesystem']['s3']['options']['bucket'])
                ->replaceArgument(2, [
                    'create' => $this->config['filesystem']['s3']['options']['create'],
                    'acl' => $this->config['filesystem']['s3']['options']['acl'],
                    'directory' => $this->config['filesystem']['s3']['options']['directory']
                ]);

            $container->getDefinition('netbull_media.metadata.amazon')
                ->addArgument([
                    'acl' => $this->config['filesystem']['s3']['options']['acl'],
                    'storage' => $this->config['filesystem']['s3']['options']['storage'],
                    'encryption' => $this->config['filesystem']['s3']['options']['encryption'],
                    'meta' => $this->config['filesystem']['s3']['options']['meta'],
                    'cache_control' => $this->config['filesystem']['s3']['options']['cache_control']
                ]);
        } else {
            $container->removeDefinition('netbull_media.adapter.filesystem.s3');
            $container->removeDefinition('netbull_media.filesystem.s3');
        }

        // If there is no local or s3 filesystem then remove the local.server service
        if (
            (!$container->hasDefinition('netbull_media.adapter.filesystem.local') || !$container->hasDefinition('netbull_media.adapter.filesystem.s3')) &&
            $container->hasDefinition('netbull_media.adapter.filesystem.local.server')
        ) {
            $container->removeDefinition('netbull_media.adapter.filesystem.local.server');
        }

        // Remove the local.server definition if the S3 does not use credentials for authentication
        if ($container->hasDefinition('netbull_media.adapter.filesystem.local.server') && empty($this->config['filesystem']['s3']['defaults']['credentials'])) {
            $container->removeDefinition('netbull_media.adapter.filesystem.local.server');
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    public function configureCdnAdapter(ContainerBuilder $container): void
    {
        // add the default configuration for the server cdn
        if ($container->hasDefinition('netbull_media.cdn.server') && isset($this->config['cdn']['server'])) {
            $container->getDefinition('netbull_media.cdn.server')
                ->replaceArgument(0, $this->config['cdn']['server']['path'])
                ->replaceArgument(1, $this->config['cdn']['server']['paths']);
        } else {
            $container->removeDefinition('netbull_media.cdn.server');
        }
        // add the default configuration for the server cdn
        if ($container->hasDefinition('netbull_media.cdn.local.server') && isset($this->config['filesystem']['local'])) {
            $container->getDefinition('netbull_media.cdn.local.server')
                ->replaceArgument(0, $this->config['cdn']['server']['path'])
                ->replaceArgument(1, $this->config['cdn']['dev']['path'])
                ->replaceArgument(2, $this->config['filesystem']['local']['directory'])
                ->replaceArgument(3, $this->config['cdn']['server']['paths']);
        } else {
            $container->removeDefinition('netbull_media.cdn.local.server');
        }
    }

    /**
     * @param ContainerBuilder  $container
     */
    public function configureProviders(ContainerBuilder $container): void
    {
        $container->getDefinition('netbull_media.provider.image')
            ->replaceArgument(6, new Reference($this->config['providers']['image']['adapter']))
            ->replaceArgument(7, array_map('strtolower', $this->config['providers']['image']['allowed_extensions']))
            ->replaceArgument(8, $this->config['providers']['image']['allowed_mime_types']);

        $container->getDefinition('netbull_media.provider.file')
            ->replaceArgument(6, $this->config['providers']['file']['allowed_extensions'])
            ->replaceArgument(7, $this->config['providers']['file']['allowed_mime_types']);

        $container->getDefinition('netbull_media.provider.youtube')
            ->replaceArgument(5, $this->config['providers']['youtube']['html5']);
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return 'netbull_media';
    }
}
