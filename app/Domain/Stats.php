<?php

namespace App\Domain;

//value object для игровых статистик

class Stats
{
    public function __construct(
        private int $level,
        private int $experience,
        private int $wins,
        private int $losses
    ) {
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getExperience(): int
    {
        return $this->experience;
    }

    public function getWins(): int
    {
        return $this->wins;
    }

    public function getLosses(): int
    {
        return $this->losses;
    }

    public function toArray(): array
    {
        return [
            'level' => $this->level,
            'experience' => $this->experience,
            'wins' => $this->wins,
            'losses' => $this->losses,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            level: (int)($data['level'] ?? 1),
            experience: (int)($data['experience'] ?? 0),
            wins: (int)($data['wins'] ?? 0),
            losses: (int)($data['losses'] ?? 0),
        );
    }
}


