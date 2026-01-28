<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Form\DataTransformer;

use NetBull\MediaBundle\Entity\Media;
use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Provider\Pool;
use Symfony\Component\Form\DataTransformerInterface;

class ProviderDataTransformer implements DataTransformerInterface
{
    protected array $options;

    public function __construct(
        protected Pool $pool,
        array $options = [],
    ) {
        $this->options = $this->getOptions($options);
    }

    protected function getOptions(array $options): array
    {
        return array_merge([
            'provider' => false,
            'context' => false,
            'empty_on_new' => true,
            'new_on_update' => true,
        ], $options);
    }

    public function transform($value): MediaInterface
    {
        if (null === $value) {
            return new Media();
        }

        return $value;
    }

    public function reverseTransform($value): ?MediaInterface
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
        if (empty($binaryContent) && null !== $value->getId()) {
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
