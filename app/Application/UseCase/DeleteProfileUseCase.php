<?php

namespace App\Application\UseCase;

use App\Domain\ProfileRepositoryInterface;
use App\Infrastructure\Security\InputValidator;

class DeleteProfileUseCase
{
    public function __construct(
        private ProfileRepositoryInterface $profiles
    ) {
    }

    // \RuntimeException если профиль не найден
    public function execute(int $id): void
    {
        // валидация id профиля
        $validatedId = InputValidator::validateProfileId($id);
        
        $profile = $this->profiles->findById($validatedId);
        
        if (!$profile) {
            throw new \RuntimeException('Profile not found');
        }
        
        $this->profiles->delete($validatedId);
    }
}


