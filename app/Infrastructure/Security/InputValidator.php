<?php

namespace App\Infrastructure\Security;

//класс для валидации и защиты от атак и некорректных данных

class InputValidator
{
    //валидация и санитизация строки защита от (xss)
    public static function sanitizeString(string $value, int $maxLength = 255): string
    {
        // удаление html тегов и экранирование специальных символов
        $sanitized = htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        
        // ограничение длинны
        if (mb_strlen($sanitized) > $maxLength) {
            $sanitized = mb_substr($sanitized, 0, $maxLength);
        }
        
        return $sanitized;
    }

    //валидация external id
    public static function validateExternalId(string $externalId): string
    {
        $externalId = trim($externalId);
        
        if (empty($externalId)) {
            throw new \InvalidArgumentException('External ID cannot be empty');
        }
        
        if (mb_strlen($externalId) > 64) {
            throw new \InvalidArgumentException('External ID is too long (max 64 characters)');
        }
        
        // только буквы цифры дефисы и подчеркивания
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $externalId)) {
            throw new \InvalidArgumentException('External ID contains invalid characters');
        }
        
        return $externalId;
    }

    //валидация nickname 
    public static function validateNickname(string $nickname): string
    {
        $nickname = trim($nickname);
        
        if (empty($nickname)) {
            throw new \InvalidArgumentException('Nickname cannot be empty');
        }
        
        if (mb_strlen($nickname) > 255) {
            throw new \InvalidArgumentException('Nickname is too long (max 255 characters)');
        }
        
        // xss защита
        return self::sanitizeString($nickname, 255);
    }

    //валидация id
    public static function validateProfileId($id): int
    {
        if (!is_numeric($id)) {
            throw new \InvalidArgumentException('Profile ID must be a number');
        }
        
        $id = (int) $id;
        
        if ($id <= 0) {
            throw new \InvalidArgumentException('Profile ID must be positive');
        }
        
        return $id;
    }

    //валидация статистики
    public static function validateStats(array $stats): array
    {
        $validated = [
            'level' => isset($stats['level']) ? self::validateInt($stats['level'], 1, 9999, 1) : 1,
            'experience' => isset($stats['experience']) ? self::validateInt($stats['experience'], 0, PHP_INT_MAX, 0) : 0,
            'wins' => isset($stats['wins']) ? self::validateInt($stats['wins'], 0, PHP_INT_MAX, 0) : 0,
            'losses' => isset($stats['losses']) ? self::validateInt($stats['losses'], 0, PHP_INT_MAX, 0) : 0,
        ];
        
        return $validated;
    }

    //валидация целого числа
    private static function validateInt($value, int $min, int $max, int $default): int
    {
        if (!is_numeric($value)) {
            return $default;
        }
        
        $intValue = (int) $value;
        
        if ($intValue < $min) {
            return $min;
        }
        
        if ($intValue > $max) {
            return $max;
        }
        
        return $intValue;
    }

    //валидация steam api ключа
    public static function validateSteamApiKey(string $apiKey): string
    {
        $apiKey = trim($apiKey);
        
        if (empty($apiKey)) {
            throw new \InvalidArgumentException('Steam API key cannot be empty');
        }
        
        // Steam API ключи обычно состоят из букв, цифр и дефисов
        if (!preg_match('/^[a-zA-Z0-9-]+$/', $apiKey)) {
            throw new \InvalidArgumentException('Invalid Steam API key format');
        }
        
        return $apiKey;
    }

    //валидация steamid
    public static function validateSteamId(string $steamId): string
    {
        $steamId = trim($steamId);
        
        if (empty($steamId)) {
            throw new \InvalidArgumentException('Steam ID cannot be empty');
        }
        
        // SteamID64 должен быть числом, начинающимся с 7656119
        if (!preg_match('/^7656119[0-9]{10}$/', $steamId)) {
            throw new \InvalidArgumentException('Invalid Steam ID format. Expected SteamID64 (e.g., 76561198012345678)');
        }
        
        return $steamId;
    }

    //валидация параметров пагинации
    public static function validatePaginationParams($limit, $offset): array
    {
        $validLimit = self::validateInt($limit, 1, 100, 50);
        $validOffset = self::validateInt($offset, 0, PHP_INT_MAX, 0);
        
        return [$validLimit, $validOffset];
    }
}

