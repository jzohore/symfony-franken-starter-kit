<?php

declare(strict_types=1);

namespace App\Application\User\UseCase;

use App\Application\User\DTO\UserDTO;
use App\Domain\User\Port\Out\UserRepositoryInterface;
use App\Domain\User\Entity\User;
use App\Domain\User\Port\In\UpdateUserInterface;
use Symfony\Component\Uid\Uuid;
use function Symfony\Component\Clock\now;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;


final readonly class UpdateUserUseCase implements UpdateUserInterface
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
        
                $this->mapper->map($dto, $user);
                $this->userRepository->save($user);
        
                return $user;
    }
}