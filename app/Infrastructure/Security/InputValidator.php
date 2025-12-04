<?php

namespace App\Infrastructure\Security;

/**
 * Класс для валидации и санитизации входных данных.
 * Защита от XSS атак и некорректных данных.
 */
class InputValidator
{
    /**
     * Валидация и санитизация строки (защита от XSS).
     */
    public static function sanitizeString(string $value, int $maxLength = 255): string
    {
        // Удаляем HTML теги и экранируем специальные символы
        $sanitized = htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        
        // Ограничиваем длину
        if (mb_strlen($sanitized) > $maxLength) {
            $sanitized = mb_substr($sanitized, 0, $maxLength);
        }
        
        return $sanitized;
    }

    /**
     * Валидация external ID (SteamID и т.д.).
     */
    public static function validateExternalId(string $externalId): string
    {
        $externalId = trim($externalId);
        
        if (empty($externalId)) {
            throw new \InvalidArgumentException('External ID cannot be empty');
        }
        
        if (mb_strlen($externalId) > 64) {
            throw new \InvalidArgumentException('External ID is too long (max 64 characters)');
        }
        
        // Разрешаем только буквы, цифры, дефисы и подчеркивания
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $externalId)) {
            throw new \InvalidArgumentException('External ID contains invalid characters');
        }
        
        return $externalId;
    }

    /**
     * Валидация nickname.
     */
    public static function validateNickname(string $nickname): string
    {
        $nickname = trim($nickname);
        
        if (empty($nickname)) {
            throw new \InvalidArgumentException('Nickname cannot be empty');
        }
        
        if (mb_strlen($nickname) > 255) {
            throw new \InvalidArgumentException('Nickname is too long (max 255 characters)');
        }
        
        // Санитизация от XSS
        return self::sanitizeString($nickname, 255);
    }

    /**
     * Валидация ID профиля.
     */
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

    /**
     * Валидация статистики.
     */
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

    /**
     * Валидация целого числа.
     */
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

    /**
     * Валидация Steam API ключа.
     */
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

    /**
     * Валидация SteamID.
     */
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

    /**
     * Валидация параметров пагинации.
     */
    public static function validatePaginationParams($limit, $offset): array
    {
        $validLimit = self::validateInt($limit, 1, 100, 50);
        $validOffset = self::validateInt($offset, 0, PHP_INT_MAX, 0);
        
        return [$validLimit, $validOffset];
    }
}

