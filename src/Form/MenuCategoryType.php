<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MenuCategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Nom de la catégorie', 'required' => false])
            ->add('items', TextareaType::class, [
                'label' => 'Lignes (une par ligne : Nom | Prix)',
                'required' => false,
                'attr' => ['rows' => 5],
                'help' => 'Ex: Planche à partager | 14€ — laissez le prix vide pour afficher un tiret',
            ])
            ->add('note', TextType::class, ['label' => 'Note sous la catégorie', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }
}
