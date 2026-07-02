<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'media')]
class Media
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Nom de fichier stocké dans public/uploads/media */
    #[ORM\Column(length: 255)]
    private string $filename = '';

    /** Variante WebP générée à l'upload (nullable si GD indisponible) */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $webpFilename = null;

    /** Miniature 480px générée à l'upload */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $thumbFilename = null;

    #[ORM\Column(length: 255)]
    private string $originalName = '';

    #[ORM\Column(length: 255)]
    private string $alt = '';

    #[ORM\Column(length: 100)]
    private string $mimeType = '';

    #[ORM\Column]
    private int $size = 0;

    #[ORM\Column(nullable: true)]
    private ?int $width = null;

    #[ORM\Column(nullable: true)]
    private ?int $height = null;

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

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function getWebpFilename(): ?string
    {
        return $this->webpFilename;
    }

    public function setWebpFilename(?string $webpFilename): static
    {
        $this->webpFilename = $webpFilename;

        return $this;
    }

    public function getThumbFilename(): ?string
    {
        return $this->thumbFilename;
    }

    public function setThumbFilename(?string $thumbFilename): static
    {
        $this->thumbFilename = $thumbFilename;

        return $this;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): static
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getAlt(): string
    {
        return $this->alt;
    }

    public function setAlt(string $alt): static
    {
        $this->alt = $alt;

        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): static
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(?int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** URL publique du fichier (WebP en priorité si disponible) */
    public function getPublicPath(): string
    {
        return '/uploads/media/'.$this->filename;
    }

    public function getWebpPublicPath(): ?string
    {
        return $this->webpFilename ? '/uploads/media/'.$this->webpFilename : null;
    }

    public function getThumbPublicPath(): ?string
    {
        return $this->thumbFilename ? '/uploads/media/'.$this->thumbFilename : null;
    }

    /** Meilleure URL à insérer dans un bloc */
    public function getBestPublicPath(): string
    {
        return $this->getWebpPublicPath() ?? $this->getPublicPath();
    }
}
