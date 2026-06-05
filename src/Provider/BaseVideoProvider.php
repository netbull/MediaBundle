<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Provider;

use Gaufrette\File;
use Gaufrette\Filesystem;
use Imagine\Image\Box;
use NetBull\MediaBundle\Cdn\CdnInterface;
use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Exception\VideoMetadataException;
use NetBull\MediaBundle\Http\UrlSecurityChecker;
use NetBull\MediaBundle\Metadata\MetadataBuilderInterface;
use NetBull\MediaBundle\Thumbnail\ThumbnailInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class BaseVideoProvider extends BaseProvider
{
    protected HttpClientInterface $httpClient;

    protected ?MetadataBuilderInterface $metadata;

    protected LoggerInterface $logger;

    protected UrlSecurityChecker $urlSecurityChecker;

    public function __construct(string $name, Filesystem $filesystem, CdnInterface $cdn, ThumbnailInterface $thumbnail, ?MetadataBuilderInterface $metadata = null)
    {
        parent::__construct($name, $filesystem, $cdn, $thumbnail);

        $this->httpClient = HttpClient::create();
        $this->metadata = $metadata;
        $this->logger = new NullLogger();
        $this->urlSecurityChecker = new UrlSecurityChecker();
    }

    public function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Let the host application inject a configured (e.g. scoped / proxied) HTTP client so outgoing
     * requests to video providers pass through its egress controls. Falls back to the default
     * client when no service is wired.
     */
    public function setHttpClient(?HttpClientInterface $httpClient): void
    {
        if (null !== $httpClient) {
            $this->httpClient = $httpClient;
        }
    }

    public function getReferenceImage(array|MediaInterface $media): string
    {
        return $media->getMetadataValue('thumbnail_url');
    }

    public function getReferenceFile(array|MediaInterface $media): ?File
    {
        $key = $this->generatePrivateUrl($media, 'reference');

        // the reference file is remote, get it and store it with the 'reference' format
        if ($this->getFilesystem()->has($key)) {
            $referenceFile = $this->getFilesystem()->get($key);
        } else {
            $referenceFile = $this->getFilesystem()->get($key, true);
            $metadata = $this->metadata ? $this->metadata->get($referenceFile->getName()) : [];
            $thumbnailUrl = $this->getReferenceImage($media);

            if (!$thumbnailUrl) {
                $this->logger->info('[netbull_media] No reference thumbnail URL for media {id}', [
                    'id' => $media instanceof MediaInterface ? $media->getId() : null,
                ]);

                return null;
            }

            // SSRF guard: the thumbnail URL comes from provider metadata (attacker-influenceable),
            // so refuse anything that is not http(s) to a public host.
            if (!$this->urlSecurityChecker->isAllowed($thumbnailUrl)) {
                $this->logger->warning('[netbull_media] Refusing to fetch reference thumbnail for media {id}: URL {url} is not allowed (SSRF protection)', [
                    'id' => $media instanceof MediaInterface ? $media->getId() : null,
                    'url' => $thumbnailUrl,
                ]);

                return null;
            }

            try {
                $referenceFile->setContent(
                    // max_redirects: 0 — do not follow a redirect that could target an internal host.
                    $this->httpClient->request('GET', $thumbnailUrl, ['max_redirects' => 0])->getContent(),
                    $metadata,
                );
            } catch (ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
                $this->logger->warning('[netbull_media] Failed to fetch reference thumbnail for media {id} from {url}: {error}', [
                    'id' => $media instanceof MediaInterface ? $media->getId() : null,
                    'url' => $thumbnailUrl,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        }

        return $referenceFile;
    }

    public function generatePublicUrl(array|MediaInterface $media, string $format): string
    {
        if ('reference' === $format) {
            $path = $this->getReferenceImage($media);
        } else {
            $path = $this->thumbnail->generatePublicUrl($this, $media, $format);
        }

        return $this->getCdn()->getPath($path);
    }

    /**
     * Video thumbnails are served publicly from the CDN, and the view controller redirects video
     * media to the external provider (YouTube/Vimeo/Youku) instead of serving bytes — so there is
     * no access-controlled endpoint to sign. This intentionally returns the public URL ($identifier
     * and $expires are not applicable); use an image/file provider when true signed access is needed.
     */
    public function generateSecuredUrl(array|MediaInterface $media, string $format, string $identifier, int $expires = 300): string
    {
        return $this->generatePublicUrl($media, $format);
    }

    public function generatePrivateUrl(MediaInterface $media, string $format): string
    {
        return \sprintf('%s/thumb_%s_%s.jpg',
            $this->generatePath($media),
            $media->getId(),
            $format,
        );
    }

    public function buildMediaType(FormBuilderInterface $formBuilder, array $options = []): void
    {
        $mainField = $options['main_field'];
        unset($options['main_field']);
        if (!$mainField) {
            $options = [
                'label' => 'YouTube URL',
            ];
        }

        $formBuilder
            ->add('providerName', ChoiceType::class, [
                'label' => false,
                'choices' => [
                    'YouTube' => 'netbull_media.provider.youtube',
                    'Vimeo' => 'netbull_media.provider.vimeo',
                    'Youku' => 'netbull_media.provider.youku',
                ],
                'label_attr' => [
                    'class' => 'text-default radio-inline',
                ],
                'expanded' => true,
                'multiple' => false,
                'empty_data' => 'netbull_media.provider.youtube',
            ])
            ->add('binaryContent', TextType::class, array_merge($options, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'e.g. https://www.youtube.com/watch?v=7sXMUJROuS8',
                    'class' => 'videoUrl',
                ],
            ]))
            ->add('caption', TextType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'Caption',
                ],
                'required' => false,
            ]);

        if ($mainField) {
            $formBuilder->add('main', RadioType::class, [
                'label' => 'Main',
                'attr' => [
                    'class' => 'video-main',
                ],
                'required' => false,
            ]);
        }
    }

    public function buildShortMediaType(FormBuilderInterface $formBuilder, array $options = []): void
    {
        $formBuilder->add('newBinaryContent', TextType::class, array_merge([
            'label' => 'YouTube URL',
            'required' => false,
            'attr' => [
                'placeholder' => 'e.g. https://www.youtube.com/watch?v=7sXMUJROuS8',
                'class' => 'videoUrl',
            ],
        ], $options));
    }

    public function postUpdate(MediaInterface $media): void
    {
        $this->postPersist($media);
    }

    public function postPersist(MediaInterface $media): void
    {
        if (!$media->getBinaryContent()) {
            return;
        }

        $media->resetBinaryContent();
    }

    public function postRemove(MediaInterface $media): void
    {
    }

    protected function getMetadata(string $url): array
    {
        try {
            $response = $this->httpClient->request('GET', $url);
        } catch (TransportExceptionInterface $e) {
            throw new VideoMetadataException('Unable to retrieve the video information for :' . $url, 0, $e);
        }

        try {
            $metadata = $response->toArray();
        } catch (ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            throw new VideoMetadataException('Unable to retrieve the video information for :' . $url, 0, $e);
        }

        if (!$metadata) {
            throw new VideoMetadataException('Unable to decode the video information for :' . $url);
        }

        return $metadata;
    }

    protected function getBoxHelperProperties(MediaInterface $media, string $format, array $options = []): Box
    {
        if ('reference' === $format) {
            return $media->getBox();
        }

        if (isset($options['width']) || isset($options['height'])) {
            $settings = [
                'width' => $options['width'] ?? null,
                'height' => $options['height'] ?? null,
            ];
        } else {
            $settings = $this->getFormat($format);
        }

        return $this->resizer->getBox($media, $settings);
    }

    public function postFlush(MediaInterface $media): void
    {
        $this->generateThumbnails($media);
    }
}
