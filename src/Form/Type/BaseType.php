<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Form\Type;

use NetBull\MediaBundle\Form\DataTransformer\ProviderDataTransformer;
use NetBull\MediaBundle\Provider\Pool;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

abstract class BaseType extends AbstractType
{
    public function __construct(protected Pool $pool)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new ProviderDataTransformer($this->pool, [
            'provider' => $options['provider'],
            'context' => $options['context'],
            'empty_on_new' => $options['empty_on_new'],
            'new_on_update' => $options['new_on_update'],
        ]));
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['context'] = $options['context'];
        $view->vars['provider'] = $options['provider'];
    }
}
