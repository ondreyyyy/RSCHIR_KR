<?php

namespace App\Infrastructure\Broadcast;

use App\Application\Ports\BroadcasterInterface;
use Pusher\Pusher;

/**
 * Реализация BroadcasterInterface через сервис Pusher.
 *
 * На защите можно показать, что используется готовый WebSocket-провайдер,
 * но доменный/приложенческий код от него не зависит.
 */
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
        // Проверяем, настроен ли Pusher (если ключи пустые, пропускаем broadcast)
        $appId = $_ENV['PUSHER_APP_ID'] ?? '';
        $key = $_ENV['PUSHER_KEY'] ?? '';
        $secret = $_ENV['PUSHER_SECRET'] ?? '';
        
        if (empty($appId) || empty($key) || empty($secret)) {
            // Pusher не настроен - просто пропускаем broadcast без ошибки
            // Это позволяет работать приложению без real-time функционала
            return;
        }
        
        try {
            $this->pusher->trigger($channel, $event, $payload);
        } catch (\Exception $e) {
            // Логируем ошибку, но не прерываем выполнение
            // Это позволяет обновлять статистику даже если Pusher недоступен
            error_log('Pusher broadcast error: ' . $e->getMessage());
        }
    }
}


