<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'navigation_item')]
class NavigationItem
{
    public const LOCATION_HEADER = 'header';
    public const LOCATION_FOOTER = 'footer';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $label = '';

    /** URL ou ancre (ex: /contact, #menu) */
    #[ORM\Column(length: 255)]
    private string $url = '';

    #[ORM\Column(length: 20)]
    private string $location = self::LOCATION_HEADER;

    #[ORM\Column]
    private int $position = 0;

    /** Affiché comme bouton CTA (dernier élément du header en général) */
    #[ORM\Column]
    private bool $isButton = false;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;

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

    public function isButton(): bool
    {
        return $this->isButton;
    }

    public function setIsButton(bool $isButton): static
    {
        $this->isButton = $isButton;

        return $this;
    }
}
