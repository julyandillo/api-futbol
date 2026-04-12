<?php

namespace App\Form;

use App\Config\CategoriaCompeticion;
use App\Config\TipoCompeticion;
use App\Entity\Competicion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class CompeticionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', null, [
                'label' => new TranslatableMessage('Name', [], 'forms'),
                'row_attr' => ['class' => 'form-enlinea'],
            ])
            ->add('tipo', EnumType::class, [
                'label' => new TranslatableMessage('Type', [], 'forms'),
                'class' => TipoCompeticion::class,
                'choice_label' => function ($choice, $key, $value) {
                    return $choice->nameForSelect();
                },
                'row_attr' => ['class' => 'form-enlinea'],
            ])
            ->add('categoria', EnumType::class, [
                'label' => new TranslatableMessage('Category', [], 'forms'),
                'class' => CategoriaCompeticion::class,
                'choice_label' => function ($choice, $key, $value) {
                    return $choice->nameForSelect();
                },
                'row_attr' => ['class' => 'form-enlinea'],
            ])
            ->add('fecha_inicio', DateType::class, [
                'label' => new TranslatableMessage('Start date', [], 'forms'),
                'widget' => 'single_text',
                'html5' => true,
                'row_attr' => ['class' => 'form-enlinea'],
            ])
            ->add('fecha_fin', DateType::class, [
                'label' => new TranslatableMessage('End date', [], 'forms'),
                'widget' => 'single_text',
                'html5' => true,
                'row_attr' => ['class' => 'form-enlinea'],
            ])
            ->add('guardar', SubmitType::class, [
                'label' => new TranslatableMessage('Save', [], 'forms'),
                'attr' => [
                    'class' => 'boton boton-primary',
                ],
                'row_attr' => ['class' => 'form-enlinea'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Competicion::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'competiciones_token',
        ]);
    }
}
