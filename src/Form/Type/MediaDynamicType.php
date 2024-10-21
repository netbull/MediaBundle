<?php

namespace NetBull\MediaBundle\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use NetBull\MediaBundle\Entity\Media;

class MediaDynamicType extends BaseType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $this->pool->getProvider($options['provider'])->buildMediaType($builder, [
            'label' => false,
            'main_field' => $options['main_field'],
            'locale' => $options['locale'],
        ]);
    }

    /**
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        $view->vars['main_field'] = $options['main_field'];
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
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
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'netbull_media_dynamic_type';
    }
}
