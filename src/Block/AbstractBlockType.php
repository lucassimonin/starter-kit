<?php

namespace App\Block;

abstract class AbstractBlockType implements BlockTypeInterface
{
    public function getTemplate(): string
    {
        return sprintf('blocks/%s.html.twig', $this->getKey());
    }

    public function getDescription(): string
    {
        return '';
    }
}
