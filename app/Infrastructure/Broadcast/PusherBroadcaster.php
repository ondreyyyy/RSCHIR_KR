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
        $this->pusher->trigger($channel, $event, $payload);
    }
}


