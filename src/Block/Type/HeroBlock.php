<?php

namespace App\Block\Type;

use App\Block\AbstractBlockType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class HeroBlock extends AbstractBlockType
{
    public function getKey(): string
    {
        return 'hero';
    }

    public function getLabel(): string
    {
        return 'Hero plein écran';
    }

    public function getDescription(): string
    {
        return 'Image de fond, titre, accroche et deux boutons d\'action.';
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('kicker', TextType::class, ['label' => 'Surtitre', 'required' => false, 'help' => 'Petite ligne au-dessus du titre (ex: Marché du Lez · Montpellier)'])
            ->add('title', TextType::class, ['label' => 'Titre', 'required' => false])
            ->add('subtitle', TextType::class, ['label' => 'Sous-titre', 'required' => false, 'help' => 'Ligne en majuscules sous le titre'])
            ->add('tagline', TextType::class, ['label' => 'Accroche (italique)', 'required' => false])
            ->add('text', TextareaType::class, ['label' => 'Texte', 'required' => false, 'attr' => ['rows' => 3]])
            ->add('image', TextType::class, ['label' => 'Image de fond (URL)', 'required' => false, 'help' => 'Collez une URL depuis la bibliothèque Médias'])
            ->add('image_alt', TextType::class, ['label' => 'Texte alternatif de l\'image', 'required' => false])
            ->add('primary_label', TextType::class, ['label' => 'Bouton principal — libellé', 'required' => false])
            ->add('primary_link', TextType::class, ['label' => 'Bouton principal — lien ou ancre', 'required' => false, 'help' => 'Ex: #menu ou /contact'])
            ->add('secondary_label', TextType::class, ['label' => 'Bouton secondaire — libellé', 'required' => false])
            ->add('secondary_link', TextType::class, ['label' => 'Bouton secondaire — lien ou ancre', 'required' => false]);
    }

    public function getDefaultData(): array
    {
        return [
            'kicker' => 'Surtitre',
            'title' => 'Titre principal',
            'subtitle' => '',
            'tagline' => '',
            'text' => '',
            'image' => '',
            'image_alt' => '',
            'primary_label' => '',
            'primary_link' => '',
            'secondary_label' => '',
            'secondary_link' => '',
        ];
    }
}
