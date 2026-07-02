<?php

namespace App\Service;

use App\Entity\Setting;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Réglages globaux clé/valeur (nom du site, logo, email de contact…).
 * Chargés une fois par requête.
 */
class SettingsProvider
{
    /** @var array<string, ?string>|null */
    private ?array $cache = null;

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $this->load();

        return $this->cache[$key] ?? $default;
    }

    /** @return array<string, ?string> */
    public function all(): array
    {
        $this->load();

        return $this->cache;
    }

    public function set(string $key, ?string $value): void
    {
        $repo = $this->em->getRepository(Setting::class);
        $setting = $repo->findOneBy(['key' => $key]) ?? (new Setting())->setKey($key);
        $setting->setValue($value);
        $this->em->persist($setting);
        $this->cache = null;
    }

    private function load(): void
    {
        if (null !== $this->cache) {
            return;
        }
        $this->cache = [];
        foreach ($this->em->getRepository(Setting::class)->findAll() as $setting) {
            $this->cache[$setting->getKey()] = $setting->getValue();
        }
    }
}
