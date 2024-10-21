<?php

namespace NetBull\MediaBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use NetBull\MediaBundle\Entity\Media;

class FileType extends BaseType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $this->pool->getProvider($options['provider'])->buildShortMediaType($builder, [
            'label' => false,
            'attr' => [
                'accept' => join(',', $options['allowed_types']),
            ],
        ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
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

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'netbull_media_file_type';
    }
}
