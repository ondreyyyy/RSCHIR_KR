<?php

namespace App\Application\UseCase;

use App\Domain\ProfileRepositoryInterface;

class DeleteProfileUseCase
{
    public function __construct(
        private ProfileRepositoryInterface $profiles
    ) {
    }

    public function execute(int $id): void
    {
        $this->profiles->delete($id);
    }
}


