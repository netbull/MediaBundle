<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Provider;

use NetBull\MediaBundle\Entity\MediaInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class VimeoProvider extends BaseVideoProvider
{
    protected bool $html5;

    public function getHelperProperties(array|MediaInterface $media, string $format, array $options = []): array
    {
        if ($media instanceof MediaInterface) {
            if ('reference' === $format) {
                $box = $media->getBox();
            } else {
                $resizerFormat = $this->getFormat($format);
                if (false === $resizerFormat) {
                    throw new RuntimeException(\sprintf('The image format "%s" is not defined.
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
        } else {
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

    public function getViewProperties(array|MediaInterface $media, string $format, array $options = []): array
    {
        // documentation : http://vimeo.com/api/docs/moogaloop
        $defaults = [
            // (optional) Flash Player version of app. Defaults to 9 .NEW!
            // 10 - New Moogaloop. 9 - Old Moogaloop without the newest features.
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

            // (optional) JS function called when the player loads. Defaults to vimeo_player_loaded.
            'js_onLoad' => 0,

            // Unique id that is passed into all player events as the ending parameter.
            'js_swf_id' => uniqid('vimeo_player_'),
        ];

        $player_parameters = array_merge($defaults, $options['player_parameters'] ?? []);

        return [
            'src' => http_build_query($player_parameters),
            'id' => $player_parameters['js_swf_id'],
            'frameborder' => $options['frameborder'] ?? 0,
            'width' => $media instanceof MediaInterface ? $media->getWidth() : $media['width'],
            'height' => $media instanceof MediaInterface ? $media->getHeight() : $media['height'],
        ];
    }

    protected function fixBinaryContent(MediaInterface $media): void
    {
        if (!$media->getBinaryContent()) {
            return;
        }

        if (preg_match("/vimeo\.com\/(\d+)/", $media->getBinaryContent(), $matches)) {
            $media->setBinaryContent($matches[1]);
        }
    }

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

    public function updateMetadata(MediaInterface $media, bool $force = false): void
    {
        $url = \sprintf('https://vimeo.com/api/oembed.json?url=https://vimeo.com/%s', $media->getProviderReference());

        try {
            $metadata = $this->getMetadata($url);
        } catch (RuntimeException) {
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

    public function getDownloadResponse(MediaInterface $media, string $format, string $mode, array $headers = []): Response
    {
        return new RedirectResponse(\sprintf('https://vimeo.com/%s', $media->getProviderReference()), 302, $headers);
    }

    public function getViewResponse(MediaInterface $media, string $format, array $headers = []): Response
    {
        return new RedirectResponse(\sprintf('https://vimeo.com/%s', $media->getProviderReference()), 302, $headers);
    }
}
