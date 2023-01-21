<?php

namespace NetBull\MediaBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use NetBull\MediaBundle\Entity\Media;
use NetBull\MediaBundle\Provider\Pool;
use NetBull\MediaBundle\Entity\MediaInterface;

class ProviderDataTransformer implements DataTransformerInterface
{
    /**
     * @var Pool
     */
    protected $pool;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param Pool   $pool
     * @param array  $options
     */
    public function __construct(Pool $pool, array $options = [])
    {
        $this->pool = $pool;
        $this->options = $this->getOptions($options);
    }

    /**
     * @param array $options
     * @return array
     */
    protected function getOptions(array $options): array
    {
        return array_merge([
            'provider' => false,
            'context' => false,
            'empty_on_new' => true,
            'new_on_update' => true,
        ], $options);
    }

    /**
     * @param $value
     * @return MediaInterface
     */
    public function transform($value)
    {
        if ($value === null) {
            return new Media();
        }

        return $value;
    }

    /**
     * @param $media
     * @return MediaInterface|null
     */
    public function reverseTransform($media)
    {
        if (!$media instanceof MediaInterface) {
            return $media;
        }

        $binaryContent = $media->getNewBinaryContent();

        // no binary
        if (empty($binaryContent)) {
            // and no media id
            if (null === $media->getId() && $this->options['empty_on_new']) {
                return null;
            } elseif ($media->getId()) {
                return $media;
            }

            return $media;
        }

        // no update, but the the media exists ...
        if (empty($binaryContent) && $media->getId() !== null) {
            return $media;
        }

        // create a new media to avoid erasing other media or not ...
        $newMedia = $this->options['new_on_update'] ? new Media() : $media;
        $newMedia->setBinaryContent($binaryContent);

        $newMedia->setProviderName($media->getProviderName());
        $newMedia->setContext($media->getContext());
        $newMedia->setBinaryContent($binaryContent);

        if (!$newMedia->getProviderName() && $this->options['provider']) {
            $newMedia->setProviderName($this->options['provider']);
        }

        if (!$newMedia->getContext() && $this->options['context']) {
            $newMedia->setContext($this->options['context']);
        }

        $provider = $this->pool->getProvider($newMedia->getProviderName());

        $provider->transform($newMedia);

        return $newMedia;
    }
}
