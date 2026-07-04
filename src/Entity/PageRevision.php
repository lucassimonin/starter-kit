<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Instantané des blocs d'une page, pris avant chaque modification.
 * Permet de restaurer un état antérieur depuis le builder.
 */
#[ORM\Entity]
#[ORM\Table(name: 'page_revision')]
class PageRevision
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Page::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Page $page = null;

    /** @var list<array{type: string, position: int, enabled: bool, data: array<string, mixed>}> */
    #[ORM\Column(type: 'json')]
    private array $snapshot = [];

    /** Description du changement qui a suivi ce snapshot */
    #[ORM\Column(length: 255)]
    private string $label = '';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

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

    /** @return list<array{type: string, position: int, enabled: bool, data: array<string, mixed>}> */
    public function getSnapshot(): array
    {
        return $this->snapshot;
    }

    /** @param list<array{type: string, position: int, enabled: bool, data: array<string, mixed>}> $snapshot */
    public function setSnapshot(array $snapshot): static
    {
        $this->snapshot = $snapshot;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
