<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Champ URL d'image avec bouton « Parcourir » ouvrant la bibliothèque
 * de médias en modale (voir media_picker_widget dans _form_theme + admin.js).
 */
class MediaPickerType extends AbstractType
{
    public function getParent(): string
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'media_picker';
    }
}
