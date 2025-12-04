<?php

namespace App\Infrastructure\Steam;

use App\Application\Ports\SteamClientInterface;

/**
 * Простейший HTTP-клиент для Steam Web API на базе file_get_contents.
 *
 * Для учебных целей достаточно, на практике лучше использовать Guzzle или curl.
 */
class SteamHttpClient implements SteamClientInterface
{
    public function fetchProfile(string $apiKey, string $steamId): array
    {
        // Используем urlencode для безопасной передачи параметров в URL
        $steamUrl = sprintf(
            'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/?key=%s&steamids=%s',
            urlencode($apiKey),
            urlencode($steamId)
        );

        $steamResponse = @file_get_contents($steamUrl);
        if ($steamResponse === false) {
            throw new \RuntimeException('Steam API unreachable');
        }

        $steamData = json_decode($steamResponse, true, 512, JSON_THROW_ON_ERROR);
        $player = $steamData['response']['players'][0] ?? null;

        if (!$player) {
            throw new \RuntimeException('Steam profile not found');
        }

        // Санитизация nickname от потенциального XSS
        $nickname = $player['personaname'] ?? ('steam_' . $steamId);
        $nickname = htmlspecialchars(strip_tags($nickname), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        
        return [
            'external_id' => $steamId,
            'nickname' => $nickname,
        ];
    }
}


