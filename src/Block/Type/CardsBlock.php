<?php

namespace App\Block\Type;

use App\Block\AbstractBlockType;
use App\Form\CardItemType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class CardsBlock extends AbstractBlockType
{
    public function getKey(): string
    {
        return 'cards';
    }

    public function getLabel(): string
    {
        return 'Cartes (concept, services…)';
    }

    public function getDescription(): string
    {
        return 'Intro centrée + grille de cartes image / titre / texte.';
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('anchor', TextType::class, ['label' => 'Ancre HTML', 'required' => false, 'help' => 'Identifiant pour les liens de menu (ex: concept → lien #concept)'])
            ->add('kicker', TextType::class, ['label' => 'Surtitre', 'required' => false])
            ->add('title', TextType::class, ['label' => 'Titre', 'required' => false])
            ->add('intro', TextareaType::class, ['label' => 'Texte d\'introduction', 'required' => false, 'attr' => ['rows' => 3], 'help' => 'HTML simple autorisé (<em>, <strong>, <br>)'])
            ->add('intro_highlight', TextType::class, ['label' => 'Phrase mise en avant', 'required' => false])
            ->add('cards', CollectionType::class, [
                'label' => 'Cartes',
                'entry_type' => CardItemType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'required' => false,
            ])
            ->add('footnote', TextareaType::class, ['label' => 'Note de bas de section', 'required' => false, 'attr' => ['rows' => 2]]);
    }

    public function getDefaultData(): array
    {
        return [
            'anchor' => '',
            'kicker' => 'Surtitre',
            'title' => 'Titre de section',
            'intro' => '',
            'intro_highlight' => '',
            'cards' => [
                ['image' => '', 'image_alt' => '', 'title' => 'Carte 1', 'text' => '', 'anchor' => ''],
            ],
            'footnote' => '',
        ];
    }
}
