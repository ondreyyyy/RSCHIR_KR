<?php

namespace App\Application\Ports;

//абстракция сервиса отправки событий во внешний websocket/realtime слой

interface BroadcasterInterface
{
    //channel логическое имя канала, event название события, payload произвольные данные события
    public function broadcast(string $channel, string $event, array $payload): void;
}


