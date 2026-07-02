<?php

namespace App\Block\Type;

use App\Block\AbstractBlockType;
use App\Form\MenuCategoryType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class MenuListBlock extends AbstractBlockType
{
    public function getKey(): string
    {
        return 'menu_list';
    }

    public function getLabel(): string
    {
        return 'Carte / menu (tarifs)';
    }

    public function getDescription(): string
    {
        return 'Catégories avec lignes nom + prix reliées par des pointillés.';
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('anchor', TextType::class, ['label' => 'Ancre HTML', 'required' => false, 'help' => 'Ex: menu → lien #menu dans la navigation'])
            ->add('kicker', TextType::class, ['label' => 'Surtitre', 'required' => false])
            ->add('title', TextType::class, ['label' => 'Titre', 'required' => false])
            ->add('intro', TextareaType::class, ['label' => 'Introduction', 'required' => false, 'attr' => ['rows' => 2], 'help' => 'HTML simple autorisé (liens <a>)'])
            ->add('categories', CollectionType::class, [
                'label' => 'Catégories',
                'entry_type' => MenuCategoryType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'required' => false,
            ]);
    }

    public function getDefaultData(): array
    {
        return [
            'anchor' => 'menu',
            'kicker' => 'La carte',
            'title' => 'Carte du moment',
            'intro' => '',
            'categories' => [
                ['title' => 'Catégorie', 'items' => "Plat exemple | 12€", 'note' => ''],
            ],
        ];
    }
}
