<?php

namespace App\Block;

use Symfony\Component\Form\FormBuilderInterface;

/**
 * Un type de bloc = 1 template front + 1 formulaire admin + des valeurs par défaut.
 * Toute classe implémentant cette interface est enregistrée automatiquement
 * dans le BlockRegistry (tag app.block_type, voir services.yaml).
 */
interface BlockTypeInterface
{
    /** Clé unique stockée en base (ex: "hero") */
    public function getKey(): string;

    /** Libellé affiché dans la palette admin */
    public function getLabel(): string;

    /** Courte description pour la palette admin */
    public function getDescription(): string;

    /** Template front, ex: "blocks/hero.html.twig" */
    public function getTemplate(): string;

    /** Construit le formulaire d'édition (les champs mappent les clés du tableau data) */
    public function buildForm(FormBuilderInterface $builder): void;

    /** @return array<string, mixed> Données par défaut à la création */
    public function getDefaultData(): array;
}
