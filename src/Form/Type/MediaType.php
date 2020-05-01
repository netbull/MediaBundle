<?php

namespace NetBull\MediaBundle\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use NetBull\MediaBundle\Entity\Media;

/**
 * Class MediaType
 * @package NetBull\MediaBundle\Form\Type
 */
class MediaType extends BaseType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $this->pool->getProvider($options['provider'])->buildMediaType($builder, [
            'label' => false,
            'main_field' => $options['main_field'],
            'locale' => $options['locale'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $view->vars['main_field'] = $options['main_field'];
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
            'main_field' => true,
            'locale' => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'netbull_media_media_type';
    }
}
