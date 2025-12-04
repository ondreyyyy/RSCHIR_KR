<?php

namespace App\Application\UseCase;

use App\Domain\ProfileRepositoryInterface;

class ListProfilesUseCase
{
    public function __construct(
        private ProfileRepositoryInterface $profiles
    ) {
    }

    public function execute(int $limit = 50, int $offset = 0): array
    {
        return $this->profiles->list($limit, $offset);
    }
}


