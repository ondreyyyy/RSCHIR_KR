<?php

namespace App\Application\UseCase;

use App\Domain\ProfileRepositoryInterface;
use App\Infrastructure\Security\InputValidator;

class ListProfilesUseCase
{
    public function __construct(
        private ProfileRepositoryInterface $profiles
    ) {
    }

    public function execute(int $limit = 50, int $offset = 0): array
    {
        // Валидация параметров пагинации
        [$validLimit, $validOffset] = InputValidator::validatePaginationParams($limit, $offset);
        
        return $this->profiles->list($validLimit, $validOffset);
    }
}


