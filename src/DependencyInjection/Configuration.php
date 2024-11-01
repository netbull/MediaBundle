<?php

namespace NetBull\MediaBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class Configuration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('netbull_media');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('default_context')->isRequired()->end()
            ->end();

        $this->addContextsSection($rootNode);
        $this->addCdnSection($rootNode);
        $this->addFilesystemSection($rootNode);
        $this->addProvidersSection($rootNode);
        $this->addResizerSection($rootNode);

        return $treeBuilder;
    }

    /**
     * @param ArrayNodeDefinition $node
     */
    private function addContextsSection(ArrayNodeDefinition $node)
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

    /**
     * @param ArrayNodeDefinition $node
     */
    private function addCdnSection(ArrayNodeDefinition $node)
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

    /**
     * @param ArrayNodeDefinition $node
     */
    private function addFilesystemSection(ArrayNodeDefinition $node)
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

    /**
     * @param ArrayNodeDefinition $node
     */
    private function addProvidersSection(ArrayNodeDefinition $node)
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
                                        'application/zip'
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

    /**
     * @param ArrayNodeDefinition $node
     */
    private function addResizerSection(ArrayNodeDefinition $node)
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
                                ->scalarNode('mode')->defaultValue('inset')->end()
                            ->end()
                        ->end()
                        ->arrayNode('square')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('mode')->defaultValue('inset')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
