<?php

namespace App\Application\UseCase;

use App\Domain\Profile;
use App\Domain\ProfileRepositoryInterface;
use App\Infrastructure\Security\InputValidator;

class GetProfileUseCase
{
    public function __construct(
        private ProfileRepositoryInterface $profiles
    ) {
    }

    public function execute(int $id): ?Profile
    {
        // Валидация ID профиля
        $validatedId = InputValidator::validateProfileId($id);
        
        return $this->profiles->findById($validatedId);
    }
}


