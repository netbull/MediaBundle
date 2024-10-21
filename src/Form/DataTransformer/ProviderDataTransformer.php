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
    protected Pool $pool;

    /**
     * @var array
     */
    protected array $options;

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
    public function transform($value): MediaInterface
    {
        if ($value === null) {
            return new Media();
        }

        return $value;
    }

    /**
     * @param $value
     * @return MediaInterface|null
     */
    public function reverseTransform($value): MediaInterface|null
    {
        if (!$value instanceof MediaInterface) {
            return $value;
        }

        $binaryContent = $value->getNewBinaryContent();

        // no binary
        if (empty($binaryContent)) {
            // and no media id
            if (null === $value->getId() && $this->options['empty_on_new']) {
                return null;
            } elseif ($value->getId()) {
                return $value;
            }

            return $value;
        }

        // no update, but the media exists ...
        if (empty($binaryContent) && $value->getId() !== null) {
            return $value;
        }

        // create a new media to avoid erasing other media or not ...
        $newMedia = $this->options['new_on_update'] ? new Media() : $value;
        $newMedia->setBinaryContent($binaryContent);

        $newMedia->setProviderName($value->getProviderName());
        $newMedia->setContext($value->getContext());
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
