<?php

declare(strict_types=1);

namespace App\Domain\User\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use DateTimeImmutable;
use DateTimeZone;
use App\Infrastructure\User\Doctrine\UserRepository;
use App\Infrastructure\Shared\Traits\Trashable;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
final class User
{
    use Trashable;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null {
        get => $this->id;
    }

    #[ORM\Column(type: 'datetime_immutable')]
    public DateTimeImmutable $createdAt {
        get => $this->createdAt;
    }

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    public ?DateTimeImmutable $updatedAt {
        get => $this->updatedAt;
        set => $this->updatedAt = $value;
    }

    public function __construct()
    {
        $tz = new DateTimeZone('Europe/Paris');
        $this->createdAt = new DateTimeImmutable('now', $tz);
        $this->updatedAt = new DateTimeImmutable('now', $tz);
    }

    // TODO: ajoutez vos champs de domaine ici.
}