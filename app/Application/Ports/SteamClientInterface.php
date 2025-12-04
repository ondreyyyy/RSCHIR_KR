<?php

namespace App\Application\Ports;

/**
 * Порт для обращения к Steam Web API.
 *
 * Благодаря этому интерфейсу доменный/приложенческий код не зависит
 * от конкретной HTTP-библиотеки или структуры ответов.
 */
interface SteamClientInterface
{
    /**
     * Получить базовую информацию о профиле игрока по SteamID.
     *
     * @return array Ассоциативный массив с нормализованными данными профиля.
     *               Обязательные ключи: external_id, nickname.
     */
    public function fetchProfile(string $apiKey, string $steamId): array;
}


