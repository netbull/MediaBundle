<?php
namespace NetBull\MediaBundle\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use NetBull\MediaBundle\Entity\Media;
use NetBull\MediaBundle\Provider\Pool;
use NetBull\MediaBundle\Form\DataTransformer\ProviderDataTransformer;

/**
 * Class MediaType
 * @package NetBull\MediaBundle\Form\Type
 */
class MediaType extends AbstractType
{
    /**
     * @var Pool
     */
    protected $pool;

    /**
     * @var string
     */
    protected $class;

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
            'provider'      => $options['provider'],
            'context'       => $options['context'],
            'empty_on_new'  => $options['empty_on_new'],
            'new_on_update' => $options['new_on_update']
        ]));

        $this->pool->getProvider($options['provider'])->buildMediaType($builder, [
            'label'         => false,
            'main_field'    => $options['main_field'],
            'locale'        => $options['locale'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['main_field']   = $options['main_field'];
        $view->vars['context']      = $options['context'];
        $view->vars['provider']     = $options['provider'];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'    => Media::class,
            'provider'      => null,
            'context'       => null,
            'empty_on_new'  => true,
            'new_on_update' => true,
            'main_field'    => true,
            'locale'        => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'netbull_media_type';
    }
}
