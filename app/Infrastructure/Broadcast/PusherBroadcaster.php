<?php

namespace App\Infrastructure\Broadcast;

use App\Application\Ports\BroadcasterInterface;
use Pusher\Pusher;

//реализация BroadcasterInterface через pusher

class PusherBroadcaster implements BroadcasterInterface
{
    private Pusher $pusher;

    public function __construct()
    {
        $options = [
            'cluster' => $_ENV['PUSHER_CLUSTER'] ?? 'eu',
            'useTLS' => true,
        ];

        $this->pusher = new Pusher(
            $_ENV['PUSHER_KEY'] ?? 'app-key',
            $_ENV['PUSHER_SECRET'] ?? 'app-secret',
            $_ENV['PUSHER_APP_ID'] ?? 'app-id',
            $options
        );
    }

    public function broadcast(string $channel, string $event, array $payload): void
    {
        // проверка настроен ли pusher (если ключи пустые - пропуск broadcast)
        $appId = $_ENV['PUSHER_APP_ID'] ?? '';
        $key = $_ENV['PUSHER_KEY'] ?? '';
        $secret = $_ENV['PUSHER_SECRET'] ?? '';
        
        if (empty($appId) || empty($key) || empty($secret)) {
            // pusher не настроен - пропуск broadcast без ошибки (для работы приложения без realtime функционала)
            return;
        }
        
        try {
            $this->pusher->trigger($channel, $event, $payload);
        } catch (\Exception $e) {
            // логирование ошибки но без прерывания выполнение (для обновления статистики если pusher недоступен)
            error_log('Pusher broadcast error: ' . $e->getMessage());
        }
    }
}


