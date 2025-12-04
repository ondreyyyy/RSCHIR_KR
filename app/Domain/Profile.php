<?php

namespace App\Domain;

/**
 * Доменная сущность "Профиль игрока".
 *
 * В Clean Architecture доменная модель не знает ничего
 * о базе данных, HTTP и т.п.
 */
class Profile
{
    public function __construct(
        private ?int $id,
        private string $externalId, // например, SteamID или другой внешний идентификатор
        private string $nickname,
        private Stats $stats
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function getNickname(): string
    {
        return $this->nickname;
    }

    public function setNickname(string $nickname): void
    {
        $this->nickname = $nickname;
    }

    public function getStats(): Stats
    {
        return $this->stats;
    }

    public function setStats(Stats $stats): void
    {
        $this->stats = $stats;
    }
}


