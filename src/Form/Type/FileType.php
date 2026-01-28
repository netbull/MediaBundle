<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Form\Type;

use NetBull\MediaBundle\Entity\Media;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FileType extends BaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $this->pool->getProvider($options['provider'])->buildShortMediaType($builder, [
            'label' => false,
            'attr' => [
                'accept' => implode(',', $options['allowed_types']),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Media::class,
            'provider' => 'netbull_media.provider.file',
            'context' => null,
            'empty_on_new' => true,
            'new_on_update' => true,
            'allowed_types' => [],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'netbull_media_file_type';
    }
}
