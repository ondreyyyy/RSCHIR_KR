<?php

namespace App\Application\UseCase;

use App\Domain\ProfileRepositoryInterface;

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
        $profile = $this->profiles->findById($id);
        
        if (!$profile) {
            throw new \RuntimeException('Profile not found');
        }
        
        $this->profiles->delete($id);
    }
}


