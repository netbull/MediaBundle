<?php

namespace NetBull\MediaBundle\DependencyInjection;

use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class NetBullMediaExtension extends Extension
{
    /**
     * @param array $configs
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

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

        $this->configureFilesystemAdapter($container, $config);
        $this->configureCdnAdapter($container, $config);

        $pool = $container->getDefinition('netbull_media.pool');
        $pool->replaceArgument(0, $config['default_context']);

        // this shameless hack is done in order to have one clean configuration
        // for adding formats ....
        $pool->addMethodCall('__hack__', $config);

        $container->setParameter('netbull_media.resizer.simple.adapter.mode', $config['resizer']['simple']['mode']);
        $container->setParameter('netbull_media.resizer.square.adapter.mode', $config['resizer']['square']['mode']);

        $strategies = [];
        foreach ($config['contexts'] as $name => $settings) {
            $formats = [];
            foreach ($settings['formats'] as $format => $value) {
                $formats[$name.'_'.$format] = $value;
            }

            $strategies[] = $settings['download']['strategy'];
            $pool->addMethodCall('addContext', [$name, $settings['providers'], $formats, $settings['download']]);
        }

        $strategies = array_unique($strategies);
        foreach ($strategies as $strategyId) {
            $pool->addMethodCall('addDownloadSecurity', [$strategyId, new Reference($strategyId)]);
        }

        $this->configureBuzz($container, $config);
        $this->configureProviders($container, $config);
    }

    /**
     * Inject filesystem dependency to default provider.
     *
     * @param ContainerBuilder  $container
     * @param array             $config
     */
    public function configureFilesystemAdapter(ContainerBuilder $container, array $config)
    {
        // add the default configuration for the local filesystem
        if ($container->hasDefinition('netbull_media.adapter.filesystem.local') && isset($config['filesystem']['local'])) {
            $container->getDefinition('netbull_media.adapter.filesystem.local')
                ->addArgument($config['filesystem']['local']['directory'])
                ->addArgument($config['filesystem']['local']['create'])
            ;
        } else {
            $container->removeDefinition('netbull_media.adapter.filesystem.local');
        }

        // add the default configuration for the S3 filesystem
        if ($container->hasDefinition('netbull_media.adapter.filesystem.s3') && isset($config['filesystem']['s3'])) {
            $container->getDefinition('netbull_media.wrapper.s3')
                ->replaceArgument(0, [
                    'version'       => $config['filesystem']['s3']['defaults']['version'],
                    'region'        => $config['filesystem']['s3']['defaults']['region'],
                    'credentials'   => $config['filesystem']['s3']['defaults']['credentials']
                ]);

            $container->getDefinition('netbull_media.adapter.filesystem.s3')
                ->replaceArgument(1, $config['filesystem']['s3']['options']['bucket'])
                ->replaceArgument(2, [
                    'create'    => $config['filesystem']['s3']['options']['create'],
                    'acl'       => $config['filesystem']['s3']['options']['acl'],
                    'directory' => $config['filesystem']['s3']['options']['directory']
                ]);

            $container->getDefinition('netbull_media.metadata.amazon')
                ->addArgument([
                    'acl'           => $config['filesystem']['s3']['options']['acl'],
                    'storage'       => $config['filesystem']['s3']['options']['storage'],
                    'encryption'    => $config['filesystem']['s3']['options']['encryption'],
                    'meta'          => $config['filesystem']['s3']['options']['meta'],
                    'cache_control' => $config['filesystem']['s3']['options']['cache_control']
                ])
            ;
        } else {
            $container->removeDefinition('netbull_media.adapter.filesystem.s3');
            $container->removeDefinition('netbull_media.filesystem.s3');
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     */
    public function configureBuzz(ContainerBuilder $container, array $config)
    {
        $responseFactory = $container->getDefinition('netbull_media.buzz.response_factory');
        $container->getDefinition('netbull_media.buzz.browser')
            ->addArgument(new Reference($config['buzz']['connector']))
            ->addArgument($responseFactory);

        foreach (['netbull_media.buzz.connector.curl', 'netbull_media.buzz.connector.file_get_contents'] as $connector) {
            $container->getDefinition($connector)
                ->addArgument([
                    'allow_redirects' => $config['buzz']['client']['allow_redirects'],
                    'max_redirects' => $config['buzz']['client']['max_redirects'],
                    'timeout' => $config['buzz']['client']['timeout'],
                    'verify' => $config['buzz']['client']['verify'],
                    'proxy' => $config['buzz']['client']['proxy'],
                ])
                ->addArgument($responseFactory);
        }
    }

    /**
     * Inject CDN dependency to default provider.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param array                                                   $config
     */
    public function configureCdnAdapter(ContainerBuilder $container, array $config)
    {
        // add the default configuration for the server cdn
        if ($container->hasDefinition('netbull_media.cdn.server') && isset($config['cdn']['server'])) {
            $container->getDefinition('netbull_media.cdn.server')
                ->replaceArgument(0, $config['cdn']['server']['path'])
                ->replaceArgument(1, $config['cdn']['server']['paths'])
            ;
        } else {
            $container->removeDefinition('netbull_media.cdn.server');
        }
        // add the default configuration for the server cdn
        if ($container->hasDefinition('netbull_media.cdn.local.server') && isset($config['filesystem']['local'])) {
            $container->getDefinition('netbull_media.cdn.local.server')
                ->replaceArgument(0, $config['cdn']['server']['path'])
                ->replaceArgument(1, $config['cdn']['server']['paths'])
                ->replaceArgument(2, $config['cdn']['dev']['path'])
                ->replaceArgument(3, $config['filesystem']['local']['directory'])
            ;
        } else {
            $container->removeDefinition('netbull_media.cdn.local.server');
        }
    }

    /**
     * @param ContainerBuilder  $container
     * @param array             $config
     */
    public function configureProviders(ContainerBuilder $container, $config)
    {
        $container->getDefinition('netbull_media.provider.image')
            ->replaceArgument(4, array_map('strtolower', $config['providers']['image']['allowed_extensions']))
            ->replaceArgument(5, $config['providers']['image']['allowed_mime_types'])
            ->replaceArgument(6, new Reference($config['providers']['image']['adapter']))
        ;

        $container->getDefinition('netbull_media.provider.file')
            ->replaceArgument(4, $config['providers']['file']['allowed_extensions'])
            ->replaceArgument(5, $config['providers']['file']['allowed_mime_types'])
        ;

        $container->getDefinition('netbull_media.provider.youtube')->replaceArgument(6, $config['providers']['youtube']['html5']);
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return 'netbull_media';
    }
}
