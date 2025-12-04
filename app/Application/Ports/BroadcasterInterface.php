<?php

namespace App\Application\Ports;

/**
 * Абстракция сервиса отправки событий во внешний WebSocket/real-time слой.
 *
 * В реализации можно использовать Pusher, Laravel Reverb, Socket.IO и т.п.
 */
interface BroadcasterInterface
{
    /**
     * @param string $channel Логическое имя канала, например "profiles"
     * @param string $event   Название события, например "stats.updated"
     * @param array  $payload Произвольные данные события
     */
    public function broadcast(string $channel, string $event, array $payload): void;
}


