<?php

declare(strict_types=1);

namespace NetBull\MediaBundle;

use Exception;
use Imagine\Image\ManipulatorInterface;
use NetBull\MediaBundle\DependencyInjection\Compiler\AddProviderCompilerPass;
use NetBull\MediaBundle\Provider\Pool;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class NetBullMediaBundle extends AbstractBundle
{
    protected string $extensionAlias = 'netbull_media';

    public function configure(DefinitionConfigurator $definition): void
    {
        $rootNode = $definition->rootNode();

        $rootNode
            ->children()
                ->scalarNode('default_context')->isRequired()->end()
            ->end();

        $this->addContextsSection($rootNode);
        $this->addCdnSection($rootNode);
        $this->addFilesystemSection($rootNode);
        $this->addProvidersSection($rootNode);
        $this->addResizerSection($rootNode);
    }

    /**
     * @throws Exception
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.yaml');

        // Store processed config for compiler pass
        $builder->setParameter('netbull_media.config', $config);

        $this->configureFilesystemAdapter($builder, $config);
        $this->configureCdnAdapter($builder, $config);

        $pool = $builder->getDefinition(Pool::class);
        $pool->replaceArgument(0, $config['default_context']);

        $builder->setParameter('netbull_media.resizer.simple.adapter.mode', 'outbound' === $config['resizer']['simple']['mode'] ? ManipulatorInterface::THUMBNAIL_OUTBOUND : ManipulatorInterface::THUMBNAIL_INSET);
        $builder->setParameter('netbull_media.resizer.square.adapter.mode', 'outbound' === $config['resizer']['square']['mode'] ? ManipulatorInterface::THUMBNAIL_OUTBOUND : ManipulatorInterface::THUMBNAIL_INSET);

        foreach (['netbull_media.resizer.simple', 'netbull_media.resizer.square'] as $resizerId) {
            if ($builder->hasDefinition($resizerId)) {
                $builder->getDefinition($resizerId)
                    ->replaceArgument(0, new Reference('netbull_media.adapter.image.' . $config['resizer']['adapter']));
            }
        }

        $downloadStrategies = $viewStrategies = [];
        foreach ($config['contexts'] as $name => $settings) {
            $formats = [];
            foreach ($settings['formats'] as $format => $value) {
                $formats[$name . '_' . $format] = $value;
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

        $this->configureProviders($builder, $config);
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new AddProviderCompilerPass());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    private function addContextsSection($node): void
    {
        $node
            ->children()
                ->arrayNode('contexts')
                    ->useAttributeAsKey('id')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('download')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('strategy')->defaultValue('netbull_media.security.superadmin_strategy')->end()
                                    ->scalarNode('mode')->defaultValue('http')->end()
                                ->end()
                            ->end()
                            ->arrayNode('view')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('strategy')->defaultValue('netbull_media.security.superadmin_strategy')->end()
                                    ->scalarNode('mode')->defaultValue('http')->end()
                                ->end()
                            ->end()
                            ->arrayNode('providers')
                                ->prototype('scalar')
                                    ->defaultValue([])
                                ->end()
                            ->end()
                            ->arrayNode('formats')
                                ->isRequired()
                                ->useAttributeAsKey('id')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('width')->defaultNull()->end()
                                        ->scalarNode('height')->defaultNull()->end()
                                        ->scalarNode('quality')->defaultValue(80)->end()
                                        ->scalarNode('format')->defaultValue('jpg')->end()
                                        ->scalarNode('constraint')->defaultValue(true)->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addCdnSection($node): void
    {
        $node
            ->children()
                ->arrayNode('cdn')
                    ->children()
                        ->arrayNode('server')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('path')->defaultValue('/uploads/media')->end()
                                ->arrayNode('paths')
                                    ->treatNullLike([])
                                    ->prototype('scalar')->end()
                                    ->defaultValue([])
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('dev')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('path')->defaultValue('')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addFilesystemSection($node): void
    {
        $node
            ->children()
                ->arrayNode('filesystem')
                    ->children()
                        ->arrayNode('local')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('directory')->defaultValue('%kernel.project_dir%/public/uploads/media')->end()
                                ->scalarNode('create')->defaultValue(false)->end()
                            ->end()
                        ->end()

                        ->arrayNode('s3')
                            ->children()
                                ->arrayNode('defaults')
                                    ->children()
                                        ->scalarNode('directory')->defaultValue('')->end()
                                        ->scalarNode('version')->defaultValue('latest')->end()
                                        ->scalarNode('region')->defaultValue('eu-central-1')->end()
                                        ->arrayNode('credentials')
                                            ->children()
                                                ->scalarNode('key')->isRequired()->end()
                                                ->scalarNode('secret')->isRequired()->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()

                                ->arrayNode('options')
                                    ->children()
                                        ->scalarNode('bucket')->isRequired()->end()
                                        ->scalarNode('directory')->defaultValue('')->end()
                                        ->scalarNode('create')->defaultValue(false)->end()
                                        ->scalarNode('storage')
                                            ->defaultValue('standard')
                                            ->validate()
                                                ->ifNotInArray(['standard', 'reduced'])
                                                ->thenInvalid('Invalid storage type - "%s"')
                                            ->end()
                                        ->end()
                                        ->scalarNode('cache_control')->defaultValue('604800')->end()
                                        ->scalarNode('acl')
                                            ->defaultValue('public')
                                            ->validate()
                                                ->ifNotInArray(['private', 'public-read', 'open', 'auth_read', 'owner_read', 'owner_full_control'])
                                                ->thenInvalid('Invalid acl permission - "%s"')
                                            ->end()
                                        ->end()
                                        ->scalarNode('encryption')
                                            ->defaultValue('')
                                            ->validate()
                                                ->ifNotInArray(['aes256'])
                                                ->thenInvalid('Invalid encryption type - "%s"')
                                            ->end()
                                        ->end()
                                        ->arrayNode('meta')
                                            ->useAttributeAsKey('name')
                                            ->prototype('scalar')
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()

                    ->end()
                ->end()
            ->end();
    }

    private function addProvidersSection($node): void
    {
        $node
            ->children()
                ->arrayNode('providers')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('file')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('service')->defaultValue('netbull_media.provider.file')->end()
                                ->scalarNode('resizer')->defaultValue(false)->end()
                                ->scalarNode('filesystem')->defaultValue('netbull_media.filesystem.s3')->end()
                                ->scalarNode('cdn')->defaultValue('netbull_media.cdn.server')->end()
                                ->arrayNode('allowed_extensions')
                                    ->prototype('scalar')->end()
                                    ->defaultValue([
                                        'pdf', 'txt', 'rtf',
                                        'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
                                        'odt', 'odg', 'odp', 'ods', 'odc', 'odf', 'odb',
                                        'csv',
                                        'xml',
                                    ])
                                ->end()
                                ->arrayNode('allowed_mime_types')
                                    ->prototype('scalar')->end()
                                    ->defaultValue([
                                        'application/pdf', 'application/x-pdf', 'application/rtf', 'text/html', 'text/rtf', 'text/plain',
                                        'application/excel', 'application/msword', 'application/vnd.ms-excel', 'application/vnd.ms-powerpoint',
                                        'application/vnd.ms-powerpoint', 'application/vnd.oasis.opendocument.text', 'application/vnd.oasis.opendocument.graphics', 'application/vnd.oasis.opendocument.presentation', 'application/vnd.oasis.opendocument.spreadsheet', 'application/vnd.oasis.opendocument.chart', 'application/vnd.oasis.opendocument.formula', 'application/vnd.oasis.opendocument.database', 'application/vnd.oasis.opendocument.image',
                                        'text/comma-separated-values',
                                        'text/xml', 'application/xml',
                                        'application/zip',
                                    ])
                                ->end()
                            ->end()
                        ->end()

                        ->arrayNode('image')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('service')->defaultValue('netbull_media.provider.image')->end()
                                ->scalarNode('resizer')->defaultValue('netbull_media.resizer.square')->end()
                                ->scalarNode('filesystem')->defaultValue('netbull_media.filesystem.s3')->end()
                                ->scalarNode('cdn')->defaultValue('netbull_media.cdn.server')->end()
                                ->scalarNode('adapter')->defaultValue('netbull_media.adapter.image.gd')->end()
                                ->arrayNode('allowed_extensions')
                                    ->prototype('scalar')->end()
                                    ->defaultValue(['jpg', 'png', 'jpeg'])
                                ->end()
                                ->arrayNode('allowed_mime_types')
                                    ->prototype('scalar')->end()
                                    ->defaultValue([
                                        'image/pjpeg',
                                        'image/jpeg',
                                        'image/png',
                                        'image/x-png',
                                    ])
                                ->end()
                            ->end()
                        ->end()

                        ->arrayNode('youtube')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('service')->defaultValue('netbull_media.provider.youtube')->end()
                                ->scalarNode('resizer')->defaultValue('netbull_media.resizer.simple')->end()
                                ->scalarNode('filesystem')->defaultValue('netbull_media.filesystem.s3')->end()
                                ->scalarNode('cdn')->defaultValue('netbull_media.cdn.server')->end()
                                ->scalarNode('generator')->defaultValue('netbull_media.generator.default')->end()
                                ->scalarNode('thumbnail')->defaultValue('netbull_media.thumbnail.format')->end()
                                ->scalarNode('html5')->defaultValue(true)->end()
                            ->end()
                        ->end()

                        ->arrayNode('vimeo')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('service')->defaultValue('netbull_media.provider.vimeo')->end()
                                ->scalarNode('resizer')->defaultValue('netbull_media.resizer.simple')->end()
                                ->scalarNode('filesystem')->defaultValue('netbull_media.filesystem.s3')->end()
                                ->scalarNode('cdn')->defaultValue('netbull_media.cdn.server')->end()
                                ->scalarNode('generator')->defaultValue('netbull_media.generator.default')->end()
                                ->scalarNode('thumbnail')->defaultValue('netbull_media.thumbnail.format')->end()
                            ->end()
                        ->end()

                        ->arrayNode('youku')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('service')->defaultValue('netbull_media.provider.youku')->end()
                                ->scalarNode('resizer')->defaultValue('netbull_media.resizer.simple')->end()
                                ->scalarNode('filesystem')->defaultValue('netbull_media.filesystem.s3')->end()
                                ->scalarNode('cdn')->defaultValue('netbull_media.cdn.server')->end()
                                ->scalarNode('generator')->defaultValue('netbull_media.generator.default')->end()
                                ->scalarNode('thumbnail')->defaultValue('netbull_media.thumbnail.format')->end()
                            ->end()
                        ->end()

                    ->end()
                ->end()
            ->end();
    }

    private function addResizerSection($node): void
    {
        $node
            ->children()
                ->arrayNode('resizer')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('adapter')->defaultValue('imagick')->end()
                        ->arrayNode('simple')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->enumNode('mode')
                                    ->values(['inset', 'outbound'])
                                    ->defaultValue('inset')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('square')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->enumNode('mode')
                                    ->values(['inset', 'outbound'])
                                    ->defaultValue('inset')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function configureFilesystemAdapter(ContainerBuilder $container, array $config): void
    {
        // Add the default configuration for the local filesystem
        if ($container->hasDefinition('netbull_media.adapter.filesystem.local') && isset($config['filesystem']['local'])) {
            $container->getDefinition('netbull_media.adapter.filesystem.local')
                ->replaceArgument(0, $config['filesystem']['local']['directory'])
                ->addArgument($config['filesystem']['local']['create']);
        } else {
            $container->removeDefinition('netbull_media.adapter.filesystem.local');
            $container->removeDefinition('netbull_media.filesystem.local');
        }

        // Add the default configuration for the S3 filesystem
        if ($container->hasDefinition('netbull_media.adapter.filesystem.s3') && isset($config['filesystem']['s3'])) {
            $options = [
                'version' => $config['filesystem']['s3']['defaults']['version'],
                'region' => $config['filesystem']['s3']['defaults']['region'],
            ];
            if (!empty($config['filesystem']['s3']['defaults']['credentials'])) {
                $options['credentials'] = $config['filesystem']['s3']['defaults']['credentials'];
            }
            $container->getDefinition('netbull_media.wrapper.s3')
                ->replaceArgument(0, $options);

            $container->getDefinition('netbull_media.adapter.filesystem.s3')
                ->replaceArgument(1, $config['filesystem']['s3']['options']['bucket'])
                ->replaceArgument(2, [
                    'create' => $config['filesystem']['s3']['options']['create'],
                    'acl' => $config['filesystem']['s3']['options']['acl'],
                    'directory' => $config['filesystem']['s3']['options']['directory'],
                ]);

            $container->findDefinition('netbull_media.metadata.amazon')
                ->addArgument([
                    'acl' => $config['filesystem']['s3']['options']['acl'],
                    'storage' => $config['filesystem']['s3']['options']['storage'],
                    'encryption' => $config['filesystem']['s3']['options']['encryption'],
                    'meta' => $config['filesystem']['s3']['options']['meta'],
                    'cache_control' => $config['filesystem']['s3']['options']['cache_control'],
                ]);

            // Create local.server service only when both local and S3 with credentials are configured
            if (
                $container->hasDefinition('netbull_media.adapter.filesystem.local')
                && !empty($config['filesystem']['s3']['defaults']['credentials'])
            ) {
                $container->register('netbull_media.filesystem.local.server', 'NetBull\MediaBundle\Filesystem\LocalServer')
                    ->setArguments([
                        new Reference('netbull_media.adapter.filesystem.local'),
                        new Reference('netbull_media.adapter.filesystem.s3'),
                    ])
                    ->setPublic(true);
            }
        } else {
            $container->removeDefinition('netbull_media.adapter.filesystem.s3');
            $container->removeDefinition('netbull_media.filesystem.s3');
            $container->removeDefinition('netbull_media.wrapper.s3');

            // Update the Gaufrette\Filesystem alias to point to local when S3 is not configured
            if ($container->hasAlias('Gaufrette\Filesystem') && $container->hasDefinition('netbull_media.filesystem.local')) {
                $container->setAlias('Gaufrette\Filesystem', 'netbull_media.filesystem.local');
            }
        }
    }

    private function configureCdnAdapter(ContainerBuilder $container, array $config): void
    {
        // add the default configuration for the server cdn
        if ($container->hasDefinition('netbull_media.cdn.server') && isset($config['cdn']['server'])) {
            $container->getDefinition('netbull_media.cdn.server')
                ->replaceArgument(0, $config['cdn']['server']['path'])
                ->replaceArgument(1, $config['cdn']['server']['paths']);
        } else {
            $container->removeDefinition('netbull_media.cdn.server');
        }
        // add the default configuration for the server cdn
        if ($container->hasDefinition('netbull_media.cdn.local.server') && isset($config['filesystem']['local'])) {
            $container->getDefinition('netbull_media.cdn.local.server')
                ->replaceArgument(0, $config['cdn']['server']['path'])
                ->replaceArgument(1, $config['cdn']['dev']['path'])
                ->replaceArgument(2, $config['filesystem']['local']['directory'])
                ->replaceArgument(3, $config['cdn']['server']['paths']);
        } else {
            $container->removeDefinition('netbull_media.cdn.local.server');
        }
    }

    private function configureProviders(ContainerBuilder $container, array $config): void
    {
        $container->getDefinition('netbull_media.provider.image')
            ->replaceArgument(6, new Reference($config['providers']['image']['adapter']))
            ->replaceArgument(7, array_map('strtolower', $config['providers']['image']['allowed_extensions']))
            ->replaceArgument(8, $config['providers']['image']['allowed_mime_types']);

        $container->getDefinition('netbull_media.provider.file')
            ->replaceArgument(6, $config['providers']['file']['allowed_extensions'])
            ->replaceArgument(7, $config['providers']['file']['allowed_mime_types']);

        $container->getDefinition('netbull_media.provider.youtube')
            ->replaceArgument(5, $config['providers']['youtube']['html5']);
    }
}
