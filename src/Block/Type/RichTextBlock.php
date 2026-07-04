<?php

namespace App\Block\Type;

use App\Block\AbstractBlockType;
use App\Form\RichTextType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class RichTextBlock extends AbstractBlockType
{
    public function getKey(): string
    {
        return 'rich_text';
    }

    public function getLabel(): string
    {
        return 'Texte libre';
    }

    public function getDescription(): string
    {
        return 'Section de texte HTML — mentions légales, contenu éditorial…';
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('kicker', TextType::class, ['label' => 'Surtitre', 'required' => false])
            ->add('title', TextType::class, ['label' => 'Titre', 'required' => false])
            ->add('content', RichTextType::class, [
                'label' => 'Contenu',
                'required' => false,
                'help' => 'Titres, gras, listes et liens via la barre d\'outils',
            ]);
    }

    public function getDefaultData(): array
    {
        return [
            'kicker' => '',
            'title' => 'Titre',
            'content' => '<p>Votre contenu…</p>',
        ];
    }
}
