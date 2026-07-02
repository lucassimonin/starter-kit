<?php

namespace App\Tests\Unit\Block;

use App\Block\AbstractBlockType;
use App\Block\BlockRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;

class BlockRegistryTest extends TestCase
{
    private function makeType(string $key, string $label = 'Test'): AbstractBlockType
    {
        return new class($key, $label) extends AbstractBlockType {
            public function __construct(private readonly string $key, private readonly string $label)
            {
            }

            public function getKey(): string
            {
                return $this->key;
            }

            public function getLabel(): string
            {
                return $this->label;
            }

            public function buildForm(FormBuilderInterface $builder): void
            {
            }

            public function getDefaultData(): array
            {
                return ['title' => 'Défaut'];
            }
        };
    }

    public function testHasAndGet(): void
    {
        $registry = new BlockRegistry([$this->makeType('hero'), $this->makeType('cards')]);

        $this->assertTrue($registry->has('hero'));
        $this->assertTrue($registry->has('cards'));
        $this->assertFalse($registry->has('inconnu'));
        $this->assertSame('hero', $registry->get('hero')->getKey());
    }

    public function testAllIsIndexedByKey(): void
    {
        $registry = new BlockRegistry([$this->makeType('hero')]);

        $this->assertArrayHasKey('hero', $registry->all());
        $this->assertCount(1, $registry->all());
    }

    public function testTemplateConvention(): void
    {
        $registry = new BlockRegistry([$this->makeType('menu_list')]);

        $this->assertSame('blocks/menu_list.html.twig', $registry->get('menu_list')->getTemplate());
    }

    public function testGetUnknownTypeThrows(): void
    {
        $registry = new BlockRegistry([]);

        $this->expectException(\InvalidArgumentException::class);
        $registry->get('inconnu');
    }
}
