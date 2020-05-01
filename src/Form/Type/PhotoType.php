<?php

namespace NetBull\MediaBundle\Form\Type;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use NetBull\MediaBundle\Entity\Media;
use NetBull\MediaBundle\Provider\Pool;
use NetBull\MediaBundle\Form\DataTransformer\ProviderDataTransformer;

/**
 * Class PhotoType
 * @package NetBull\MediaBundle\Form\Type
 */
class PhotoType extends BaseType
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

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $base64 = $event->getForm()->get('base64');
            if ($base64 && $base64->getData()) {
                $path = $event->getForm()->get('binaryContent')->getData()->getPathname();

                list(, $data) = explode(';', $base64->getData());
                list(, $data) = explode(',', $data);
                $data = base64_decode($data);

                file_put_contents($path, $data);
            }
        });

        $builder->add('base64', HiddenType::class, [
            'mapped' => false,
            'data' => false,
            'required' => false,
            'attr' => [
                'class' => 'base64'
            ]
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
        return 'netbull_media_photo_type';
    }
}
