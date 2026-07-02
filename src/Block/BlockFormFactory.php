<?php

namespace App\Block;

use App\Entity\Block;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Construit le formulaire d'édition d'un bloc à partir de son type.
 * Le nom du formulaire est "block_{id}" pour permettre plusieurs
 * formulaires sur la même page admin.
 */
class BlockFormFactory
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly BlockRegistry $registry,
    ) {
    }

    public function create(Block $block): FormInterface
    {
        $type = $this->registry->get($block->getType());
        $data = array_merge($type->getDefaultData(), $block->getData());

        $builder = $this->formFactory->createNamedBuilder(
            'block_'.$block->getId(),
            FormType::class,
            $data,
            ['data_class' => null],
        );

        $type->buildForm($builder);

        return $builder->getForm();
    }
}
