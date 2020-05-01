<?php

namespace NetBull\MediaBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use NetBull\MediaBundle\Entity\Media;

/**
 * Class MediaShortType
 * @package NetBull\MediaBundle\Form\Type
 */
class MediaShortType extends BaseType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $this->pool->getProvider($options['provider'])->buildShortMediaType($builder, [
            'label' => false
        ]);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'netbull_media_short_type';
    }
}
