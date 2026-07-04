<?php

namespace App\Form;

use App\Form\MediaPickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CardItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('image', MediaPickerType::class, ['label' => 'Image', 'required' => false])
            ->add('image_alt', TextType::class, ['label' => 'Texte alternatif', 'required' => false])
            ->add('title', TextType::class, ['label' => 'Titre', 'required' => false])
            ->add('text', TextareaType::class, ['label' => 'Texte', 'required' => false, 'attr' => ['rows' => 3]])
            ->add('anchor', TextType::class, ['label' => 'Ancre HTML (optionnel)', 'required' => false, 'help' => 'Ex: workshop — permet un lien #workshop vers cette carte']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }
}
