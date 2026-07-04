<?php

namespace App\Form;

use App\Entity\Post;
use App\Entity\PostCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostType extends AbstractType
{
    /** @param list<string> $appLocales */
    public function __construct(private readonly array $appLocales)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Titre de l\'article', 'empty_data' => ''])
            ->add('locale', ChoiceType::class, [
                'label' => 'Langue',
                'choices' => array_combine(array_map('strtoupper', $this->appLocales), $this->appLocales),
            ])
            ->add('slug', TextType::class, ['label' => 'Slug (URL)', 'required' => false, 'empty_data' => '', 'help' => 'Vide = généré depuis le titre. Un changement crée une redirection 301.'])
            ->add('category', EntityType::class, [
                'label' => 'Catégorie',
                'class' => PostCategory::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Sans catégorie',
            ])
            ->add('coverImage', MediaPickerType::class, ['label' => 'Image de couverture', 'required' => false])
            ->add('coverAlt', TextType::class, ['label' => 'Texte alternatif de la couverture', 'required' => false])
            ->add('excerpt', TextareaType::class, ['label' => 'Chapo (affiché dans la liste)', 'required' => false, 'attr' => ['rows' => 3]])
            ->add('content', RichTextType::class, ['label' => 'Contenu', 'required' => false, 'empty_data' => ''])
            ->add('metaTitle', TextType::class, ['label' => 'Meta title', 'required' => false, 'help' => 'Vide = titre de l\'article'])
            ->add('metaDescription', TextareaType::class, ['label' => 'Meta description', 'required' => false, 'attr' => ['rows' => 2], 'help' => 'Vide = chapo']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Post::class]);
    }
}
