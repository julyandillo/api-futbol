<?php

namespace App\Form;

use App\Config\CategoriaCompeticion;
use App\Config\TipoCompeticion;
use App\Entity\Competicion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompeticionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', null, [
                'row_attr' => ['class' => 'form-enlinea'],
            ])
            ->add('tipo', EnumType::class, [
                'class' => TipoCompeticion::class,
                'choice_label' => function ($choice, $key, $value) {
                    return match ($choice) {
                        TipoCompeticion::TorneoConLiguilla => 'Torneo con liguilla',
                        TipoCompeticion::TorneoSoloConEliminatorias => 'Eliminatorias',
                        default => $choice->name,
                    };
                },
                'row_attr' => ['class' => 'form-enlinea'],
            ])
            ->add('categoria', EnumType::class, [
                'class' => CategoriaCompeticion::class,
                'choice_label' => function ($choice, $key, $value) {
                    return match ($choice) {
                        CategoriaCompeticion::EuropeLeague => 'UEFA Europe League',
                        CategoriaCompeticion::ChampionsLeague => 'UEFA Champions League',
                        CategoriaCompeticion::CopaDelRey => 'Copa del Rey',
                        default => $choice->name,
                    };
                },
                'row_attr' => ['class' => 'form-enlinea'],
            ])
            ->add('fecha_inicio', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'row_attr' => ['class' => 'form-enlinea'],
            ])
            ->add('fecha_fin', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'row_attr' => ['class' => 'form-enlinea'],
            ])
            ->add('guardar', SubmitType::class, [
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
