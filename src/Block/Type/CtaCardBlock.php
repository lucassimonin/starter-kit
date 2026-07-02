<?php

namespace App\Block\Type;

use App\Block\AbstractBlockType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class CtaCardBlock extends AbstractBlockType
{
    public function getKey(): string
    {
        return 'cta_card';
    }

    public function getLabel(): string
    {
        return 'Carte d\'appel à l\'action';
    }

    public function getDescription(): string
    {
        return 'Encadré blanc : texte à gauche, liens et bouton à droite.';
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('anchor', TextType::class, ['label' => 'Ancre HTML', 'required' => false])
            ->add('kicker', TextType::class, ['label' => 'Surtitre', 'required' => false])
            ->add('title', TextType::class, ['label' => 'Titre', 'required' => false])
            ->add('text', TextareaType::class, ['label' => 'Texte', 'required' => false, 'attr' => ['rows' => 3]])
            ->add('links', TextareaType::class, [
                'label' => 'Liens (un par ligne : libellé | URL)',
                'required' => false,
                'attr' => ['rows' => 3],
                'help' => 'Ex: Atelier workshop | https://exemple.fr/workshop',
            ])
            ->add('button_label', TextType::class, ['label' => 'Bouton — libellé', 'required' => false])
            ->add('button_link', TextType::class, ['label' => 'Bouton — lien ou ancre', 'required' => false]);
    }

    public function getDefaultData(): array
    {
        return [
            'anchor' => '',
            'kicker' => 'Surtitre',
            'title' => 'Titre',
            'text' => '',
            'links' => '',
            'button_label' => '',
            'button_link' => '',
        ];
    }
}
