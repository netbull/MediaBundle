<?php

namespace NetBull\MediaBundle\Form\Type;

use NetBull\MediaBundle\Form\DataTransformer\ProviderDataTransformer;
use NetBull\MediaBundle\Provider\Pool;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Class BaseType
 * @package NetBull\MediaBundle\Form\Type
 */
abstract class BaseType extends AbstractType
{
    /**
     * @var Pool
     */
    protected $pool;
    /**
     * MediaType constructor.
     * @param Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new ProviderDataTransformer($this->pool, [
            'provider' => $options['provider'],
            'context' => $options['context'],
            'empty_on_new' => $options['empty_on_new'],
            'new_on_update' => $options['new_on_update'],
        ]));
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['context'] = $options['context'];
        $view->vars['provider'] = $options['provider'];
    }
}
