<?php

namespace App\Application\Ports;


//порт для обращения к steam web api

interface SteamClientInterface
{
    //получить базовую информацию о профиле игрока по steamid. array ассоциативный массив с нормализованными данными профиля
    public function fetchProfile(string $apiKey, string $steamId): array;
}


