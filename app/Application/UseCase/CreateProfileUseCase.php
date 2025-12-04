<?php

namespace App\Application\UseCase;

use App\Domain\Profile;
use App\Domain\ProfileRepositoryInterface;
use App\Domain\Stats;
use App\Infrastructure\Security\InputValidator;

class CreateProfileUseCase
{
    public function __construct(
        private ProfileRepositoryInterface $profiles
    ) {
    }

    public function execute(string $externalId, string $nickname, array $statsData): Profile
    {
        // Валидация и санитизация входных данных (защита от XSS)
        $validatedExternalId = InputValidator::validateExternalId($externalId);
        $validatedNickname = InputValidator::validateNickname($nickname);
        $validatedStats = InputValidator::validateStats($statsData);

        $stats = Stats::fromArray($validatedStats);

        $profile = new Profile(
            id: null,
            externalId: $validatedExternalId,
            nickname: $validatedNickname,
            stats: $stats
        );

        return $this->profiles->create($profile);
    }
}


