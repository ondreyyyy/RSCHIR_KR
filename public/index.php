<?php

declare(strict_types=1);

use App\Application\UseCase\CreateProfileUseCase;
use App\Application\UseCase\DeleteProfileUseCase;
use App\Application\UseCase\GetProfileUseCase;
use App\Application\UseCase\ImportProfileFromSteamUseCase;
use App\Application\UseCase\ListProfilesUseCase;
use App\Application\UseCase\UpdateStatsAndBroadcastUseCase;
use App\Domain\Profile;
use App\Infrastructure\Broadcast\PusherBroadcaster;
use App\Infrastructure\Persistence\PdoProfileRepository;
use App\Infrastructure\Security\InputValidator;
use App\Infrastructure\Steam\SteamHttpClient;

require __DIR__ . '/../vendor/autoload.php';

// Загрузка переменных из .env файла
require __DIR__ . '/../config/load-env.php';

// Простая загрузка конфигурации БД
$dbConfig = require __DIR__ . '/../config/database.php';

$pdo = new PDO(
    $dbConfig['dsn'],
    $dbConfig['user'],
    $dbConfig['password'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

// Инициализация инфраструктуры и юзкейсов
$profileRepository = new PdoProfileRepository($pdo);
$broadcaster = new PusherBroadcaster();
$steamClient = new SteamHttpClient();

$createProfile = new CreateProfileUseCase($profileRepository);
$getProfile = new GetProfileUseCase($profileRepository);
$listProfiles = new ListProfilesUseCase($profileRepository);
$deleteProfile = new DeleteProfileUseCase($profileRepository);
$updateStatsAndBroadcast = new UpdateStatsAndBroadcastUseCase($profileRepository, $broadcaster);
$importFromSteam = new ImportProfileFromSteamUseCase($profileRepository, $steamClient);

// Примитивный роутер без фреймворка
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

header('Content-Type: application/json; charset=utf-8');

try {
    // Обработка корневого маршрута
    if ($path === '/' && $method === 'GET') {
        $baseUrl = 'http://' . $_SERVER['HTTP_HOST'];
        
        echo json_encode([
            'message' => 'Game Profiles API',
            'version' => '1.0.0',
            'endpoints' => [
                'GET /profiles' => 'List profiles',
                'GET /profiles/{id}' => 'Get profile by ID',
                'POST /profiles' => 'Create profile',
                'DELETE /profiles/{id}' => 'Delete profile',
                'POST /stats/update' => 'Update stats with WebSocket broadcast',
                'POST /import/from-steam' => 'Import from Steam API',
                'GET /pusher-config' => 'Get Pusher config'
            ],
            'ui' => $baseUrl . '/ui.php',
            'realtime_test' => $baseUrl . '/realtime-test.html',
            'documentation' => 'See README.md for detailed API documentation'
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($path === '/profiles' && $method === 'GET') {
        $limit = (int) ($_GET['limit'] ?? 50);
        $offset = (int) ($_GET['offset'] ?? 0);
        // Валидация параметров пагинации
        [$validLimit, $validOffset] = InputValidator::validatePaginationParams($limit, $offset);
        $profiles = $listProfiles->execute($validLimit, $validOffset);

        $data = array_map(function (Profile $profile) {
            return [
                'id' => $profile->getId(),
                'external_id' => $profile->getExternalId(),
                'nickname' => $profile->getNickname(),
                'stats' => $profile->getStats()->toArray(),
            ];
        }, $profiles);

        echo json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        exit;
    }

    if (preg_match('#^/profiles/(\d+)$#', $path, $matches)) {
        $id = (int) $matches[1];
        // Валидация ID
        $id = InputValidator::validateProfileId($id);

        if ($method === 'GET') {
            $profile = $getProfile->execute($id);
            if (!$profile) {
                http_response_code(404);
                echo json_encode(['error' => 'Profile not found'], JSON_THROW_ON_ERROR);
                exit;
            }

            echo json_encode([
                'id' => $profile->getId(),
                'external_id' => $profile->getExternalId(),
                'nickname' => $profile->getNickname(),
                'stats' => $profile->getStats()->toArray(),
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
            exit;
        }

        if ($method === 'DELETE') {
            try {
                $deleteProfile->execute($id);
                http_response_code(204);
            } catch (RuntimeException $e) {
                http_response_code(404);
                echo json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR);
            }
            exit;
        }
    }

    if ($path === '/profiles' && $method === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

        $created = $createProfile->execute(
            externalId: $body['external_id'] ?? '',
            nickname: $body['nickname'] ?? '',
            statsData: $body['stats'] ?? []
        );

        http_response_code(201);
        echo json_encode([
            'id' => $created->getId(),
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        exit;
    }

    if ($path === '/stats/update' && $method === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        $id = (int) ($body['id'] ?? 0);
        // Валидация ID
        $id = InputValidator::validateProfileId($id);

        try {
            $updated = $updateStatsAndBroadcast->execute($id, $body['stats'] ?? []);
        } catch (RuntimeException $e) {
            http_response_code(404);
            echo json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR);
            exit;
        }

        echo json_encode([
            'id' => $updated->getId(),
            'stats' => $updated->getStats()->toArray(),
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        exit;
    }

    if ($path === '/import/from-steam' && $method === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        $apiKey = $body['key'] ?? null;
        $steamId = $body['steam_id'] ?? null;

        if (!$apiKey || !$steamId) {
            http_response_code(400);
            echo json_encode(['error' => 'key and steam_id are required'], JSON_THROW_ON_ERROR);
            exit;
        }

        try {
            $profile = $importFromSteam->execute($apiKey, $steamId);
        } catch (RuntimeException $e) {
            http_response_code(502);
            echo json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR);
            exit;
        }

        echo json_encode([
            'id' => $profile->getId(),
            'external_id' => $profile->getExternalId(),
            'nickname' => $profile->getNickname(),
            'stats' => $profile->getStats()->toArray(),
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        exit;
    }

    // Endpoint для получения публичного Pusher ключа (для тестирования real-time)
    if ($method === 'GET' && $path === '/pusher-config') {
        echo json_encode([
            'key' => $_ENV['PUSHER_KEY'] ?? '',
            'cluster' => $_ENV['PUSHER_CLUSTER'] ?? 'mt1',
        ], JSON_THROW_ON_ERROR);
        exit;
    }

    http_response_code(404);
    echo json_encode(['error' => 'Not found'], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage(),
    ], JSON_THROW_ON_ERROR);
}


