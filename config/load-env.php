<?php

//загрузка переменных из env файла
function loadEnv($filePath)
{
    if (!file_exists($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        //пропуск комментариев
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        //парсинг key=value
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            //удаление кавычек
            $value = trim($value, '"\'');
            
            //установка переменной окружения только если она ещё не установлена
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

//загрузка env файла из корня проекта
$envPath = __DIR__ . '/../.env';
loadEnv($envPath);

