<?php

namespace NetBull\MediaBundle\Provider;

use Buzz\Browser;
use Gaufrette\Filesystem;

use Symfony\Component\HttpFoundation\RedirectResponse;

use NetBull\MediaBundle\CDN\CDNInterface;
use NetBull\MediaBundle\Model\MediaInterface;
use NetBull\MediaBundle\Thumbnail\ThumbnailInterface;
use NetBull\MediaBundle\Metadata\MetadataBuilderInterface;

/**
 * Class VimeoProvider
 * @package NetBull\MediaBundle\Provider
 */
class VimeoProvider extends BaseVideoProvider
{
    /**
     * @var bool
     */
    protected $html5;

    /**
     * @param string                   $name
     * @param Filesystem               $filesystem
     * @param CDNInterface             $cdn
     * @param ThumbnailInterface       $thumbnail
     * @param Browser                  $browser
     * @param MetadataBuilderInterface $metadata
     */
    public function __construct($name, Filesystem $filesystem, CDNInterface $cdn, ThumbnailInterface $thumbnail, Browser $browser, MetadataBuilderInterface $metadata = null)
    {
        parent::__construct($name, $filesystem, $cdn, $thumbnail, $browser, $metadata);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getHelperProperties($media, $format, array $options = [])
    {
        if($media instanceof MediaInterface){
            if ($format == 'reference') {
                $box = $media->getBox();
            } else {
                $resizerFormat = $this->getFormat($format);
                if ($resizerFormat === false) {
                    throw new \RuntimeException(sprintf('The image format "%s" is not defined.
                        Is the format registered in your ``media`` configuration?', $format));
                }

                $box = $this->resizer->getBox($media, $resizerFormat);
            }
            $data = [
                'alt'      => $media->getName(),
                'title'    => $media->getName(),
                'src'      => $this->generatePublicUrl($media, $format),
                'width'    => $box->getWidth(),
                'height'   => $box->getHeight()
            ];
        }else{
            $data = [
                'alt'      => (isset($media['name']))?$media['name']:$media['caption'],
                'title'    => (isset($media['name']))?$media['name']:$media['caption'],
                'src'      => $this->generatePublicUrl($media, $format),
                'width'    => $media['width'],
                'height'   => $media['height']
            ];
        }

        return array_merge($data, $options);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getViewProperties($media, $format, array $options = [])
    {
        // documentation : http://vimeo.com/api/docs/moogaloop
        $defaults = array(
            // (optional) Flash Player version of app. Defaults to 9 .NEW!
            // 10 - New Moogaloop. 9 - Old Moogaloop without newest features.
            'fp_version'      => 10,

            // (optional) Enable fullscreen capability. Defaults to true.
            'fullscreen'      => true,

            // (optional) Show the byline on the video. Defaults to true.
            'title'           => true,

            // (optional) Show the title on the video. Defaults to true.
            'byline'          => 0,

            // (optional) Show the user's portrait on the video. Defaults to true.
            'portrait'        => true,

            // (optional) Specify the color of the video controls.
            'color'           => null,

            // (optional) Set to 1 to disable HD.
            'hd_off'          => 0,

            // Set to 1 to enable the Javascript API.
            'js_api'          => null,

            // (optional) JS function called when the player loads. Defaults to vimeo_player_loaded.
            'js_onLoad'       => 0,

            // Unique id that is passed into all player events as the ending parameter.
            'js_swf_id'       => uniqid('vimeo_player_'),
        );

        $player_parameters = array_merge($defaults, isset($options['player_parameters']) ? $options['player_parameters'] : []);

//        $box = $this->getBoxHelperProperties($media, $format, $options);

        $params = [
            'src'           => http_build_query($player_parameters),
            'id'            => $player_parameters['js_swf_id'],
            'frameborder'   => isset($options['frameborder']) ? $options['frameborder'] : 0,
            'width'         => $media instanceof MediaInterface ? $media->getWidth() : $media['width'],
            'height'        => $media instanceof MediaInterface ? $media->getHeight() : $media['height'],
        ];

        return $params;
    }

    /**
     * {@inheritdoc}
     */
    protected function fixBinaryContent(MediaInterface $media)
    {
        if (!$media->getBinaryContent()) {
            return;
        }

        if (preg_match("/vimeo\.com\/(\d+)/", $media->getBinaryContent(), $matches)) {
            $media->setBinaryContent($matches[1]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doTransform(MediaInterface $media)
    {
        $this->fixBinaryContent($media);

        if (!$media->getBinaryContent()) {
            return;
        }

        // store provider information
        $media->setProviderName($this->name);
        $media->setProviderReference($media->getBinaryContent());

        $this->updateMetadata($media, true);
    }

    /**
     * {@inheritdoc}
     */
    public function updateMetadata(MediaInterface $media, $force = false)
    {
        $url = sprintf('http://vimeo.com/api/oembed.json?url=http://vimeo.com/%s', $media->getProviderReference());

        try {
            $metadata = $this->getMetadata($media, $url);
        } catch (\RuntimeException $e) {
            $media->setEnabled(false);
            return;
        }

        // store provider information
        $media->setProviderMetadata($metadata);

        // update Media common fields from metadata
        if ($force) {
            $media->setName($metadata['title']);
        }

        $media->setHeight($metadata['height']);
        $media->setWidth($metadata['width']);
        $media->setLength($metadata['duration']);
        $media->setContentType('video/x-flv');
    }

    /**
     * {@inheritdoc}
     */
    public function getDownloadResponse(MediaInterface $media, $format, $mode, array $headers = [])
    {
        return new RedirectResponse(sprintf('http://vimeo.com/%s', $media->getProviderReference()), 302, $headers);
    }
}
