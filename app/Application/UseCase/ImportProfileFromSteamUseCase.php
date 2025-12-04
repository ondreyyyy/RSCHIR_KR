<?php

namespace App\Application\UseCase;

use App\Application\Ports\SteamClientInterface;
use App\Domain\Profile;
use App\Domain\ProfileRepositoryInterface;
use App\Domain\Stats;

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
        $data = $this->steamClient->fetchProfile($apiKey, $steamId);

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


