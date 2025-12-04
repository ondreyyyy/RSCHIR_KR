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

        // Отключаем проверку SSL сертификатов в development окружении
        // для решения проблемы с локальными сертификатами на Windows
        if (($_ENV['APP_ENV'] ?? 'production') === 'development' || 
            ($_ENV['PUSHER_DISABLE_SSL_VERIFY'] ?? 'false') === 'true') {
            $options['curl_options'] = [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ];
        }

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


