<?php

namespace App\Application\UseCase;

use App\Domain\Profile;
use App\Domain\ProfileRepositoryInterface;
use App\Domain\Stats;

class CreateProfileUseCase
{
    public function __construct(
        private ProfileRepositoryInterface $profiles
    ) {
    }

    public function execute(string $externalId, string $nickname, array $statsData): Profile
    {
        $stats = Stats::fromArray($statsData);

        $profile = new Profile(
            id: null,
            externalId: $externalId,
            nickname: $nickname,
            stats: $stats
        );

        return $this->profiles->create($profile);
    }
}


