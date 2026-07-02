<?php

namespace App\Block\Type;

use App\Block\AbstractBlockType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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
            ->add('content', TextareaType::class, [
                'label' => 'Contenu (HTML)',
                'required' => false,
                'attr' => ['rows' => 12],
                'help' => 'Balises autorisées : p, h2-h4, strong, em, a, ul, ol, li, br, img',
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
