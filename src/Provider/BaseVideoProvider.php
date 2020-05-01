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
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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

/**
 * Class BaseVideoProvider
 * @package NetBull\MediaBundle\Provider
 */
abstract class BaseVideoProvider extends BaseProvider
{
    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var MetadataBuilderInterface
     */
    protected $metadata;

    /**
     * BaseVideoProvider constructor.
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
    public function getReferenceImage($media)
    {
        return $media->getMetadataValue('thumbnail_url');
    }

    /**
     * @param array|MediaInterface $media
     * @return File|mixed
     */
    public function getReferenceFile($media)
    {
        $key = $this->generatePrivateUrl($media, 'reference');

        // the reference file is remote, get it and store it with the 'reference' format
        if ($this->getFilesystem()->has($key)) {
            $referenceFile = $this->getFilesystem()->get($key);
        } else {
            $referenceFile = $this->getFilesystem()->get($key, true);
            $metadata = $this->metadata ? $this->metadata->get($referenceFile->getName()) : [];
            try {
                $referenceFile->setContent(
                    $this->httpClient->request('GET', $this->getReferenceImage($media))->getContent(false),
                    $metadata
                );
            } catch (ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface | TransportExceptionInterface $e) {}
        }

        return $referenceFile;
    }

    /**
     * {@inheritdoc}
     */
    public function generatePublicUrl($media, $format)
    {
        if ('reference' === $format) {
            $path = $this->getReferenceImage($media);
        } else {
            $path = $this->thumbnail->generatePublicUrl($this, $media, $format);
        }

        return $this->getCdn()->getPath($path);
    }

    /**
     * {@inheritdoc}
     */
    public function generatePrivateUrl(MediaInterface $media, $format)
    {
        return sprintf('%s/thumb_%s_%s.jpg',
            $this->generatePath($media),
            $media->getId(),
            $format
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildMediaType(FormBuilderInterface $formBuilder, array $options = [])
    {
        $mainField = $options['main_field'];
        unset($options['main_field']);
        if (!$mainField) {
            $options = [
                'label' => 'YouTube URL',
            ];
        }

        $locale = $options['locale'];
        unset($options['locale']);

        $translationsOptions = [
            'fields' => [
                'caption' => [
                    'field_type'    => TextareaType::class,
                    'required'      => false
                ],
            ],
            'label' => false,
            'render_type' => 'tabs-small',
            'exclude_fields' => ['description', 'createdAt', 'updatedAt', 'createdBy', 'updatedBy', 'deletedBy'],
        ];

        if ($locale) {
            $translationsOptions['locales'] = [$locale];
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
            ])
        ;

        if($mainField){
            $formBuilder
                ->add('main', RadioType::class, [
                    'label' => 'Main',
                    'attr' => [
                        'class' => 'video-main'
                    ],
                    'required'  => false,
                ])
            ;
        }
    }

    /**
     * @param FormBuilderInterface $formBuilder
     * @param array $options
     */
    public function buildShortMediaType(FormBuilderInterface $formBuilder, array $options = [])
    {
        $formBuilder
            ->add('newBinaryContent', TextType::class, [
                'label' => 'YouTube URL',
                'required' => false,
                'attr' => [
                    'placeholder' => 'e.g. https://www.youtube.com/watch?v=7sXMUJROuS8',
                    'class' => 'videoUrl',
                ]
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function postUpdate(MediaInterface $media)
    {
        $this->postPersist($media);
    }

    /**
     * {@inheritdoc}
     */
    public function postPersist(MediaInterface $media)
    {
        if (!$media->getBinaryContent()) {
            return;
        }

        $this->generateThumbnails($media);

        $media->resetBinaryContent();
    }

    /**
     * {@inheritdoc}
     */
    public function postRemove(MediaInterface $media){ }

    /**
     * @throws RuntimeException
     *
     * @param string $url
     *
     * @return mixed
     */
    protected function getMetadata($url)
    {
        try {
            $response = $this->httpClient->request('GET', $url);
        } catch (TransportExceptionInterface $e) {
            throw new RuntimeException('Unable to retrieve the video information for :'.$url, null, $e);
        }

        try {
            $metadata = $response->toArray(false);
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
    protected function getBoxHelperProperties(MediaInterface $media, $format, $options = [])
    {
        if ('reference' === $format) {
            return $media->getBox();
        }

        if (isset($options['width']) || isset($options['height'])) {
            $settings = [
                'width' => isset($options['width']) ? $options['width'] : null,
                'height' => isset($options['height']) ? $options['height'] : null,
            ];
        } else {
            $settings = $this->getFormat($format);
        }

        return $this->resizer->getBox($media, $settings);
    }
}
