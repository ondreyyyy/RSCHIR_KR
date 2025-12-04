
Write-Host "Запуск PHP сервера на http://localhost:8000" -ForegroundColor Cyan
Write-Host "Остановка: Ctrl+C" -ForegroundColor Yellow
Write-Host ""

try {
    $result = docker ps --filter "name=game_profiles_db" --format "{{.Names}}"
    if ($result -notmatch "game_profiles_db") {
        Write-Host "⚠ PostgreSQL контейнер не запущен. Запускаю..." -ForegroundColor Yellow
        docker-compose up -d
        Start-Sleep -Seconds 3
    }
} catch {
    Write-Host "⚠ Не удалось проверить Docker. Убедись, что PostgreSQL запущен." -ForegroundColor Yellow
}

php -S localhost:8000 -t public

