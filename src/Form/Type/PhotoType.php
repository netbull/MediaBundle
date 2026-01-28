<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Form\Type;

use NetBull\MediaBundle\Entity\Media;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PhotoType extends BaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $this->pool->getProvider($options['provider'])->buildShortMediaType($builder, [
            'label' => false,
        ]);

        $builder->addEventListener(FormEvents::SUBMIT, static function (FormEvent $event) {
            $base64 = $event->getForm()->get('base64');
            if ($base64->getData()) {
                $path = $event->getForm()->get('binaryContent')->getData()->getPathname();

                list(, $data) = explode(';', $base64->getData());
                list(, $data) = explode(',', $data);
                $data = base64_decode($data, true);

                file_put_contents($path, $data);
            }
        });

        $builder->add('base64', HiddenType::class, [
            'mapped' => false,
            'data' => false,
            'required' => false,
            'attr' => [
                'class' => 'base64',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Media::class,
            'provider' => null,
            'context' => null,
            'empty_on_new' => true,
            'new_on_update' => true,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'netbull_media_photo_type';
    }
}
