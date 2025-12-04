<?php

namespace App\Application\UseCase;

use App\Application\Ports\SteamClientInterface;
use App\Domain\Profile;
use App\Domain\ProfileRepositoryInterface;
use App\Domain\Stats;
use App\Infrastructure\Security\InputValidator;

class ImportProfileFromSteamUseCase
{
    public function __construct(
        private ProfileRepositoryInterface $profiles,
        private SteamClientInterface $steamClient
    ) {
    }

    /**
     * Импортирует или обновляет профиль из Steam по API-ключу и SteamID.
     */
    public function execute(string $apiKey, string $steamId): Profile
    {
        // Валидация Steam API ключа и SteamID
        $validatedApiKey = InputValidator::validateSteamApiKey($apiKey);
        $validatedSteamId = InputValidator::validateSteamId($steamId);
        
        $data = $this->steamClient->fetchProfile($validatedApiKey, $validatedSteamId);

        $profile = $this->profiles->findByExternalId($data['external_id']);

        if ($profile) {
            $profile->setNickname($data['nickname']);
            // при необходимости можно маппить статистику, здесь — пустой объект
            $profile->setStats(Stats::fromArray([]));
            $this->profiles->update($profile);

            return $profile;
        }

        $profile = new Profile(
            id: null,
            externalId: $data['external_id'],
            nickname: $data['nickname'],
            stats: Stats::fromArray([])
        );

        return $this->profiles->create($profile);
    }
}


