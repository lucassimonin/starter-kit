<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'block')]
class Block
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Page::class, inversedBy: 'blocks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Page $page = null;

    /** Clé du type de bloc (hero, cards, menu_list…) — voir App\Block\Type */
    #[ORM\Column(length: 50)]
    private string $type = '';

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private bool $enabled = true;

    /** @var array<string, mixed> Contenu du bloc */
    #[ORM\Column(type: 'json')]
    private array $data = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPage(): ?Page
    {
        return $this->page;
    }

    public function setPage(?Page $page): static
    {
        $this->page = $page;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    /** @return array<string, mixed> */
    public function getData(): array
    {
        return $this->data;
    }

    /** @param array<string, mixed> $data */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }
}
