<?php

declare(strict_types=1);

namespace App\Domain\User\Port\Out;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Uid\Uuid;
use App\Domain\User\Entity\User;

interface UserRepositoryInterface
{
    /**
     * @param Uuid $id
     */
    public function getById(Uuid $id): ?User;

    /**
     * @param User $entity
     */
    public function save(User $entity): void;

    /**
     * @param User $entity
     */
    public function delete(User $entity): void;

    /**
     * @param string|null $querySearch
     * @param array|null $status
     * @param bool|null $isDeleted
     * @return QueryBuilder
     */
    public function search(?string $querySearch = null, ?array $status = [], ?bool $isDeleted = null): QueryBuilder;
}