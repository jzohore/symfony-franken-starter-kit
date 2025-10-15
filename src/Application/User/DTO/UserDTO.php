<?php

declare(strict_types=1);

namespace App\Application\User\DTO;

use App\Domain\User\Entity\User;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Uid\Uuid;
use DateTimeImmutable;

#[Map(target: User::class)]
final class UserDTO
{
    /**
     * @var Uuid|null
     */
    public ?Uuid $uuid = null;

    /**
     * @var DateTimeImmutable|null
     */
    public ?DateTimeImmutable $updatedAt = null;

    /**
     * @var DateTimeImmutable|null
     */
    public ?DateTimeImmutable $deletedAt = null;

    public static function fromEntity(User $entity): self
    {
        $dto = new self();
        $dto->uuid = $entity->id;
        $dto->updatedAt = $entity->updatedAt;

        return $dto;
    }
}