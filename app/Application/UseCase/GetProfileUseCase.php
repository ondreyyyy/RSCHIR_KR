<?php

namespace App\Application\UseCase;

use App\Domain\Profile;
use App\Domain\ProfileRepositoryInterface;

class GetProfileUseCase
{
    public function __construct(
        private ProfileRepositoryInterface $profiles
    ) {
    }

    public function execute(int $id): ?Profile
    {
        return $this->profiles->findById($id);
    }
}


