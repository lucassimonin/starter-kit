<?php

namespace App\Block\Type;

use App\Block\AbstractBlockType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ContactBlock extends AbstractBlockType
{
    public function getKey(): string
    {
        return 'contact';
    }

    public function getLabel(): string
    {
        return 'Contact (infos + formulaire + carte)';
    }

    public function getDescription(): string
    {
        return 'Coordonnées, horaires, formulaire avec anti-spam et carte Google Maps.';
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('anchor', TextType::class, ['label' => 'Ancre HTML', 'required' => false, 'help' => 'Ex: contact → lien #contact'])
            ->add('kicker', TextType::class, ['label' => 'Surtitre', 'required' => false])
            ->add('title', TextType::class, ['label' => 'Titre', 'required' => false])
            ->add('place_name', TextType::class, ['label' => 'Nom du lieu', 'required' => false])
            ->add('address', TextareaType::class, ['label' => 'Adresse', 'required' => false, 'attr' => ['rows' => 2]])
            ->add('access_note', TextType::class, ['label' => 'Note d\'accès', 'required' => false])
            ->add('hours', TextType::class, ['label' => 'Horaires', 'required' => false])
            ->add('phone', TextType::class, ['label' => 'Téléphone (affiché)', 'required' => false])
            ->add('phone_href', TextType::class, ['label' => 'Téléphone (format tel:)', 'required' => false, 'help' => 'Ex: +33681378275'])
            ->add('call_label', TextType::class, ['label' => 'Libellé du bouton d\'appel', 'required' => false])
            ->add('form_title', TextType::class, ['label' => 'Formulaire — titre', 'required' => false])
            ->add('form_intro', TextType::class, ['label' => 'Formulaire — introduction', 'required' => false])
            ->add('subjects', TextareaType::class, ['label' => 'Sujets du formulaire (un par ligne)', 'required' => false, 'attr' => ['rows' => 3]])
            ->add('recipient', TextType::class, ['label' => 'Email destinataire des demandes', 'required' => false, 'help' => 'Vide = email de contact des réglages généraux'])
            ->add('map_embed', TextType::class, ['label' => 'URL d\'intégration Google Maps', 'required' => false, 'help' => 'maps.google.com/maps?q=LAT,LNG&z=15&output=embed']);
    }

    public function getDefaultData(): array
    {
        return [
            'anchor' => 'contact',
            'kicker' => 'Infos pratiques',
            'title' => 'Nous trouver',
            'place_name' => '',
            'address' => '',
            'access_note' => '',
            'hours' => '',
            'phone' => '',
            'phone_href' => '',
            'call_label' => 'Appeler',
            'form_title' => 'Contact',
            'form_intro' => '',
            'subjects' => "Réservation table\nAutre question",
            'recipient' => '',
            'map_embed' => '',
        ];
    }
}
