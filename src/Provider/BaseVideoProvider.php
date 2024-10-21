<?php

namespace NetBull\MediaBundle\Provider;

use Gaufrette\File;
use Imagine\Image\Box;
use Gaufrette\Filesystem;
use RuntimeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use NetBull\MediaBundle\Cdn\CdnInterface;
use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Thumbnail\ThumbnailInterface;
use NetBull\MediaBundle\Metadata\MetadataBuilderInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class BaseVideoProvider extends BaseProvider
{
    /**
     * @var HttpClientInterface
     */
    protected HttpClientInterface $httpClient;

    /**
     * @var MetadataBuilderInterface|null
     */
    protected ?MetadataBuilderInterface $metadata;

    /**
     * @param string $name
     * @param Filesystem $filesystem
     * @param CdnInterface $cdn
     * @param ThumbnailInterface $thumbnail
     * @param MetadataBuilderInterface|null $metadata
     */
    public function __construct(string $name, Filesystem $filesystem, CdnInterface $cdn, ThumbnailInterface $thumbnail, MetadataBuilderInterface $metadata = null)
    {
        parent::__construct($name, $filesystem, $cdn, $thumbnail);

        $this->httpClient = HttpClient::create();
        $this->metadata = $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getReferenceImage(array|MediaInterface $media): string
    {
        return $media->getMetadataValue('thumbnail_url');
    }

    /**
     * @param array|MediaInterface $media
     * @return File|null
     */
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
                return null;
            }

            try {
                $referenceFile->setContent(
                    $this->httpClient->request('GET', $thumbnailUrl)->getContent(),
                    $metadata
                );
            } catch (ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface | TransportExceptionInterface) {
                return null;
            }
        }

        return $referenceFile;
    }

    /**
     * @param array|MediaInterface $media
     * @param string $format
     * @return string
     */
    public function generatePublicUrl(array|MediaInterface $media, string$format): string
    {
        if ('reference' === $format) {
            $path = $this->getReferenceImage($media);
        } else {
            $path = $this->thumbnail->generatePublicUrl($this, $media, $format);
        }

        return $this->getCdn()->getPath($path);
    }

    /**
     * @param array|MediaInterface $media
     * @param string $format
     * @param string $identifier
     * @param int $expires
     *
     * @return string
     */
    public function generateSecuredUrl(array|MediaInterface $media, string $format, string $identifier, int $expires = 300): string
    {
        if ('reference' === $format) {
            $path = $this->getReferenceImage($media);
        } else {
            $path = $this->thumbnail->generatePublicUrl($this, $media, $format);
        }

        return $this->getCdn()->getPath($path);
    }

    /**
     * @param MediaInterface $media
     * @param string $format
     * @return string
     */
    public function generatePrivateUrl(MediaInterface $media, string $format): string
    {
        return sprintf('%s/thumb_%s_%s.jpg',
            $this->generatePath($media),
            $media->getId(),
            $format
        );
    }

    /**
     * @param FormBuilderInterface $formBuilder
     * @param array $options
     * @return void
     */
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
                    'class' => 'text-default radio-inline'
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
                ]
            ]))
            ->add('caption', TextType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'Caption'
                ],
                'required' => false,
            ]);

        if ($mainField) {
            $formBuilder->add('main', RadioType::class, [
                'label' => 'Main',
                'attr' => [
                    'class' => 'video-main'
                ],
                'required'  => false,
            ]);
        }
    }

    /**
     * @param FormBuilderInterface $formBuilder
     * @param array $options
     */
    public function buildShortMediaType(FormBuilderInterface $formBuilder, array $options = []): void
    {
        $formBuilder->add('newBinaryContent', TextType::class, [
            'label' => 'YouTube URL',
            'required' => false,
            'attr' => [
                'placeholder' => 'e.g. https://www.youtube.com/watch?v=7sXMUJROuS8',
                'class' => 'videoUrl',
            ]
        ]);
    }

    /**
     * @param MediaInterface $media
     * @return void
     */
    public function postUpdate(MediaInterface $media): void
    {
        $this->postPersist($media);
    }

    /**
     * @param MediaInterface $media
     * @return void
     */
    public function postPersist(MediaInterface $media): void
    {
        if (!$media->getBinaryContent()) {
            return;
        }

        $media->resetBinaryContent();
    }

    /**
     * @param MediaInterface $media
     * @return void
     */
    public function postRemove(MediaInterface $media): void
    { }

    /**
     * @param string $url
     * @return array
     */
    protected function getMetadata(string $url): array
    {
        try {
            $response = $this->httpClient->request('GET', $url);
        } catch (TransportExceptionInterface $e) {
            throw new RuntimeException('Unable to retrieve the video information for :'.$url, null, $e);
        }

        try {
            $metadata = $response->toArray();
        } catch (ClientExceptionInterface | DecodingExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface | TransportExceptionInterface $e) {
            throw new RuntimeException('Unable to retrieve the video information for :'.$url, null, $e);
        }

        if (!$metadata) {
            throw new RuntimeException('Unable to decode the video information for :'.$url);
        }

        return $metadata;
    }

    /**
     * @param MediaInterface $media
     * @param string $format
     * @param array $options
     *
     * @return Box
     */
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

    /**
     * @param MediaInterface $media
     * @return void
     */
    public function postFlush(MediaInterface $media): void
    {
        $this->generateThumbnails($media);
    }
}
