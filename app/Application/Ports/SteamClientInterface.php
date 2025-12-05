<?php

namespace App\Application\Ports;


//порт для обращения к steam web api

interface SteamClientInterface
{
    /**
     * Получить базовую информацию о профиле игрока по steamid
     * @return array Ассоциативный массив с нормализованными данными профиля (external_id, nickname)
     */
    public function fetchProfile(string $apiKey, string $steamId): array;
}


