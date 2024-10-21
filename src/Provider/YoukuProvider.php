<?php

namespace NetBull\MediaBundle\Provider;

use Gaufrette\Filesystem;
use RuntimeException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use NetBull\MediaBundle\Cdn\CdnInterface;
use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Thumbnail\ThumbnailInterface;
use NetBull\MediaBundle\Metadata\MetadataBuilderInterface;
use Symfony\Component\HttpFoundation\Response;

class YoukuProvider extends BaseVideoProvider
{
    /**
     * @param array|MediaInterface $media
     * @param string $format
     * @param array $options
     * @return array
     */
    public function getHelperProperties(array|MediaInterface $media, string $format, array $options = []): array
    {
        if($media instanceof MediaInterface){
            if ('reference' === $format) {
                $box = $media->getBox();
            } else {
                $resizerFormat = $this->getFormat($format);
                if (false === $resizerFormat) {
                    throw new RuntimeException(sprintf('The image format "%s" is not defined.
                        Is the format registered in your ``media`` configuration?', $format));
                }

                $box = $this->resizer->getBox($media, $resizerFormat);
            }
            $data = [
                'alt' => $media->getName(),
                'title' => $media->getName(),
                'src' => $this->generatePublicUrl($media, $format),
                'width' => $box->getWidth(),
                'height' => $box->getHeight(),
            ];
        }else{
            $data = [
                'alt' => $media['name'] ?? $media['caption'],
                'title' => $media['name'] ?? $media['caption'],
                'src' => $this->generatePublicUrl($media, $format),
                'width' => $media['width'],
                'height' => $media['height'],
            ];
        }

        return array_merge($data, $options);
    }

    /**
     * @param array|MediaInterface $media
     * @param string $format
     * @param array $options
     * @return array
     */
    public function getViewProperties(array|MediaInterface $media, string $format, array $options = []): array
    {
        $defaults = [
            // (optional) Flash Player version of app. Defaults to 9 .NEW!
            'fp_version' => 10,

            // (optional) Enable fullscreen capability. Defaults to true.
            'fullscreen' => true,

            // (optional) Show the byline on the video. Defaults to true.
            'title' => true,

            // (optional) Show the title on the video. Defaults to true.
            'byline' => 0,

            // (optional) Show the user's portrait on the video. Defaults to true.
            'portrait' => true,

            // (optional) Specify the color of the video controls.
            'color' => null,

            // (optional) Set to 1 to disable HD.
            'hd_off' => 0,

            // Set to 1 to enable the Javascript API.
            'js_api' => null,

            // (optional) JS function called when the player loads. Defaults to youku_player_loaded.
            'js_onLoad' => 0,

            // Unique id that is passed into all player events as the ending parameter.
            'js_swf_id' => uniqid('youku_player_'),
        ];

        $player_parameters = array_merge($defaults, $options['player_parameters'] ?? []);

        return [
            'src' => http_build_query($player_parameters),
            'id' => $player_parameters['js_swf_id'],
            'frameborder' => $options['frameborder'] ?? 0,
            'width' => $media['width'],
            'height' => $media['height'],
        ];
    }

    /**
     * @param MediaInterface $media
     * @return void
     */
    protected function fixBinaryContent(MediaInterface $media): void
    {
        if (!$media->getBinaryContent()) {
            return;
        }

        if (preg_match("/http:\/\/v\.youku\.com\/v_show\/id_(.*)\.html(.*)/", $media->getBinaryContent(), $matches)) {
            $media->setBinaryContent($matches[1]);
        }
    }

    /**
     * @param MediaInterface $media
     * @return void
     */
    protected function doTransform(MediaInterface $media): void
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
     * @param MediaInterface $media
     * @param bool $force
     * @return void
     */
    public function updateMetadata(MediaInterface $media, bool $force = false): void
    {
        $url = sprintf('https://api.youku.com/videos/show.json?client_id=a9b2f9d6525aafb6&video_id=%s', $media->getProviderReference());

        try {
            $metadata = $this->getMetadata($url);
        } catch (RuntimeException $e) {
            $media->setEnabled(false);

            return;
        }

        // store provider information
        $media->setProviderMetadata($metadata);

        // update Media common fields from metadata
        if ($force) {
            $media->setName($metadata['title']);
        }

        $media->setHeight(400);
        $media->setWidth(480);
        $media->setLength((int)$metadata['duration']);
        $media->setContentType('video/x-flv');
    }

    /**
     * @param MediaInterface $media
     * @param $format
     * @param $mode
     * @param array $headers
     * @return Response
     */
    public function getDownloadResponse(MediaInterface $media, $format, $mode, array $headers = []): Response
    {
        return new RedirectResponse(sprintf('http://youku.com/v_show/id_%s', $media->getProviderReference()), 302, $headers);
    }

    /**
     * @param MediaInterface $media
     * @param string $format
     * @param array $headers
     * @return Response
     */
    public function getViewResponse(MediaInterface $media, string $format, array $headers = []): Response
    {
        return new RedirectResponse(sprintf('https://youku.com/v_show/id_%s', $media->getProviderReference()), 302, $headers);
    }

    /**
     * @param array|MediaInterface $media
     * @return string
     */
    public function getReferenceImage(array|MediaInterface $media): string
    {
        return $media->getMetadataValue('bigThumbnail');
    }
}
