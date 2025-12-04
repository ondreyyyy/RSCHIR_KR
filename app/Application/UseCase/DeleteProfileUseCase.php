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

    /**
     * @throws \RuntimeException Если профиль не найден.
     */
    public function execute(int $id): void
    {
        // Валидация ID профиля
        $validatedId = InputValidator::validateProfileId($id);
        
        $profile = $this->profiles->findById($validatedId);
        
        if (!$profile) {
            throw new \RuntimeException('Profile not found');
        }
        
        $this->profiles->delete($validatedId);
    }
}


