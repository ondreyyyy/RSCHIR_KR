<?php

namespace App\Application\UseCase;

use App\Application\Ports\BroadcasterInterface;
use App\Domain\Profile;
use App\Domain\ProfileRepositoryInterface;
use App\Domain\Stats;
use App\Infrastructure\Security\InputValidator;

class UpdateStatsAndBroadcastUseCase
{
    public function __construct(
        private ProfileRepositoryInterface $profiles,
        private BroadcasterInterface $broadcaster
    ) {
    }

    // \RuntimeException если профиль не найден
    public function execute(int $profileId, array $statsData): Profile
    {
        // валидация id профиля
        $validatedProfileId = InputValidator::validateProfileId($profileId);
        
        // валидация статистики
        $validatedStats = InputValidator::validateStats($statsData);

        $profile = $this->profiles->findById($validatedProfileId);

        if (!$profile) {
            throw new \RuntimeException('Profile not found');
        }

        $profile->setStats(Stats::fromArray($validatedStats));
        $updated = $this->profiles->update($profile);

        // broadcast события обновления информации
        $this->broadcaster->broadcast(
            channel: 'profiles',
            event: 'stats.updated',
            payload: [
                'id' => $updated->getId(),
                'external_id' => $updated->getExternalId(),
                'nickname' => $updated->getNickname(),
                'stats' => $updated->getStats()->toArray(),
            ]
        );

        return $updated;
    }
}


