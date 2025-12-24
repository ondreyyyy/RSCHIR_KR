<?php

namespace App\Application\Ports;


//порт для обращения к steam web api

interface SteamClientInterface
{
    /**
     * получить базовую информацию о профиле игрока по steamid
     * @return array ассоциативный массив с нормализованными данными профиля (external_id, nickname)
     */
    public function fetchProfile(string $apiKey, string $steamId): array;
}


