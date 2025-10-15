<?php

declare(strict_types=1);

namespace App\Application\User\UseCase;

use App\Application\User\DTO\UserDTO;
use App\Domain\User\Port\Out\UserRepositoryInterface;
use App\Domain\User\Entity\User;
use App\Domain\User\Port\In\RestoreUserInterface;
use Symfony\Component\Uid\Uuid;
use function Symfony\Component\Clock\now;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;


final readonly class RestoreUserUseCase implements RestoreUserInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private ObjectMapperInterface $mapper,
    ) {}

    public function __invoke(UserDTO $dto)
    {
                /** @var Uuid|null $id */
                $id = $dto->uuid;
                if (!$id) {
                    throw new NotFoundHttpException('User introuvable');
                }
        
                $user = $this->userRepository->getById($id);
                if (!$user) {
                    throw new NotFoundHttpException('User introuvable');
                }
        
                // Annuler la suppression côté DTO pour restauration
                $dto->deletedAt = null;
        
                $this->mapper->map($dto, $user);
                $this->userRepository->save($user);
    }
}