<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Form\Type;

use NetBull\MediaBundle\Entity\Media;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MediaDynamicType extends BaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $this->pool->getProvider($options['provider'])->buildMediaType($builder, [
            'label' => false,
            'main_field' => $options['main_field'],
            'locale' => $options['locale'],
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        $view->vars['main_field'] = $options['main_field'];
    }

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

    public function getBlockPrefix(): string
    {
        return 'netbull_media_dynamic_type';
    }
}
