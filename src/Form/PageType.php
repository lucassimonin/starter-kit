<?php

namespace App\Form;

use App\Entity\Page;
use App\Form\MediaPickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageType extends AbstractType
{
    /** @param list<string> $appLocales */
    public function __construct(private readonly array $appLocales)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Titre de la page', 'empty_data' => ''])
            ->add('locale', ChoiceType::class, [
                'label' => 'Langue',
                'choices' => array_combine(array_map('strtoupper', $this->appLocales), $this->appLocales),
            ])
            ->add('slug', TextType::class, ['label' => 'Slug (URL)', 'required' => false, 'empty_data' => '', 'help' => 'Laissez vide pour générer depuis le titre. Un changement crée automatiquement une redirection 301.'])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => ['Brouillon' => Page::STATUS_DRAFT, 'Publiée' => Page::STATUS_PUBLISHED],
            ])
            ->add('isHomepage', CheckboxType::class, ['label' => 'Page d\'accueil du site', 'required' => false])
            ->add('metaTitle', TextType::class, ['label' => 'Meta title', 'required' => false, 'help' => '≈ 60 caractères. Vide = titre de la page'])
            ->add('metaDescription', TextareaType::class, ['label' => 'Meta description', 'required' => false, 'attr' => ['rows' => 3], 'help' => '≈ 155 caractères'])
            ->add('ogImage', MediaPickerType::class, ['label' => 'Image de partage (Open Graph)', 'required' => false, 'help' => '1200×630px recommandé'])
            ->add('canonicalUrl', TextType::class, ['label' => 'URL canonique', 'required' => false, 'help' => 'Vide = URL de la page'])
            ->add('noindex', CheckboxType::class, ['label' => 'Exclure des moteurs de recherche (noindex)', 'required' => false])
            ->add('structuredData', TextareaType::class, ['label' => 'Données structurées JSON-LD', 'required' => false, 'attr' => ['rows' => 6], 'help' => 'JSON schema.org injecté dans la page (Restaurant, LocalBusiness…)']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Page::class]);
    }
}
