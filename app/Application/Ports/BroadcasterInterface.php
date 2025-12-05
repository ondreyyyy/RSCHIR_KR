<?php

namespace App\Application\Ports;

//абстракция сервиса отправки событий во внешний websocket/realtime слой

interface BroadcasterInterface
{
    /**
     * @param string $channel логическое имя канала
     * @param string $event   название события, например
     * @param array  $payload произвольные данные события
     */
    public function broadcast(string $channel, string $event, array $payload): void;
}


