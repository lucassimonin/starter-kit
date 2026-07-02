<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'redirect')]
class Redirect
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Chemin source, avec slash initial (ex: /ancienne-page) */
    #[ORM\Column(length: 500, unique: true)]
    private string $source = '';

    /** Chemin ou URL cible */
    #[ORM\Column(length: 500)]
    private string $target = '';

    #[ORM\Column]
    private int $statusCode = 301;

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

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): static
    {
        $this->source = '/'.ltrim($source, '/');

        return $this;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function setTarget(string $target): static
    {
        $this->target = str_starts_with($target, 'http') ? $target : '/'.ltrim($target, '/');

        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): static
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
