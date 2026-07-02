<?php

namespace App\Block\Type;

use App\Block\AbstractBlockType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class FeatureBandBlock extends AbstractBlockType
{
    public function getKey(): string
    {
        return 'feature_band';
    }

    public function getLabel(): string
    {
        return 'Bandeau contrasté + avis';
    }

    public function getDescription(): string
    {
        return 'Fond foncé : texte + liste d\'infos à gauche, témoignages à droite.';
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('kicker', TextType::class, ['label' => 'Surtitre', 'required' => false])
            ->add('title', TextType::class, ['label' => 'Titre', 'required' => false])
            ->add('text', TextareaType::class, ['label' => 'Texte', 'required' => false, 'attr' => ['rows' => 3]])
            ->add('bullets', TextareaType::class, [
                'label' => 'Infos (une par ligne : icône | texte)',
                'required' => false,
                'attr' => ['rows' => 4],
                'help' => 'Icônes Lucide, ex: calendar-days | Ouvert 7j/7 — 10h00 – 00h00',
            ])
            ->add('testimonials', TextareaType::class, [
                'label' => 'Témoignages (un par ligne : citation | auteur)',
                'required' => false,
                'attr' => ['rows' => 4],
                'help' => 'Ex: Superbe endroit, très bonne adresse. | Annie Gosse',
            ]);
    }

    public function getDefaultData(): array
    {
        return [
            'kicker' => 'Surtitre',
            'title' => 'Titre de section',
            'text' => '',
            'bullets' => '',
            'testimonials' => '',
        ];
    }
}
