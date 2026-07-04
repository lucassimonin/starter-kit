<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

/**
 * Éditeur de texte riche léger (contenteditable vanilla) :
 * titres, gras, italique, listes, liens.
 * Rendu par rich_text_editor_widget dans _form_theme + admin.js.
 */
class RichTextType extends AbstractType
{
    public function getParent(): string
    {
        return TextareaType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'rich_text_editor';
    }
}
