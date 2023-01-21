<?php

namespace NetBull\MediaBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use NetBull\MediaBundle\Entity\Media;

class MediaShortType extends BaseType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $this->pool->getProvider($options['provider'])->buildShortMediaType($builder, [
            'label' => false
        ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Media::class,
            'provider' => null,
            'context' => null,
            'empty_on_new' => true,
            'new_on_update' => true,
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'netbull_media_short_type';
    }
}
