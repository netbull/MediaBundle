<?php

namespace NetBull\MediaBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use NetBull\MediaBundle\Entity\Media;

/**
 * Class FileType
 * @package NetBull\MediaBundle\Form\Type
 */
class FileType extends BaseType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
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
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
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
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'netbull_media_file_type';
    }
}
