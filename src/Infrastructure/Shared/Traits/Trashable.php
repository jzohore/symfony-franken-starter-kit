<?php

namespace App\Infrastructure\Shared\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait Trashable
{
    private const string DELETED_SUFFIX = '_deleted_';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $deletedAt = null {
        get {
            return $this->deletedAt;
        }
    }

    public function isTrashed(): bool { return null !== $this->deletedAt; }

    public function trash(?\DateTimeImmutable $when = null): void
    {
        $this->deletedAt = $when ?? new \DateTimeImmutable();
    }

    public function restore(): void
    {
        $this->deletedAt = null;
    }

    public function generateDeletedName(string $originalName): string
    {
        // Toujours partir du nom original propre
        $cleanName = $this->extractOriginalName($originalName);

        // Génère un identifiant unique pour éviter les collisions
        $uniqueId = $this->generateUniqueDeletedSuffix();

        return $cleanName . self::DELETED_SUFFIX . $uniqueId;
    }

    public function extractOriginalName(string $deletedName): string
    {
        $pos = strpos($deletedName, self::DELETED_SUFFIX);

        return $pos !== false
            ? substr($deletedName, 0, $pos)
            : $deletedName;
    }

    public function isDeletedName(string $name): bool
    {
        return str_contains($name, self::DELETED_SUFFIX);
    }

    private function generateUniqueDeletedSuffix(): string
    {
        return date('Y-m-d_H:i:s') . '_' . mt_rand(1000, 9999);
    }
}
