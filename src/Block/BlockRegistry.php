<?php

namespace App\Block;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class BlockRegistry
{
    /** @var array<string, BlockTypeInterface> */
    private array $types = [];

    /** @param iterable<BlockTypeInterface> $blockTypes */
    public function __construct(
        #[AutowireIterator('app.block_type')] iterable $blockTypes,
    ) {
        foreach ($blockTypes as $type) {
            $this->types[$type->getKey()] = $type;
        }
    }

    public function has(string $key): bool
    {
        return isset($this->types[$key]);
    }

    public function get(string $key): BlockTypeInterface
    {
        if (!$this->has($key)) {
            throw new \InvalidArgumentException(sprintf('Type de bloc inconnu : "%s". Types disponibles : %s', $key, implode(', ', array_keys($this->types))));
        }

        return $this->types[$key];
    }

    /** @return array<string, BlockTypeInterface> */
    public function all(): array
    {
        return $this->types;
    }
}
