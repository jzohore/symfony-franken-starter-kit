<?php

declare(strict_types=1);

namespace App\Domain\User\Port\In;

use App\Application\User\DTO\UserDTO;

interface RestoreUserInterface
{
    public function __invoke(UserDTO $dto);
}