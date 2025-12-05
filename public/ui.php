<?php

declare(strict_types=1);

use App\Application\UseCase\CreateProfileUseCase;
use App\Application\UseCase\DeleteProfileUseCase;
use App\Application\UseCase\GetProfileUseCase;
use App\Application\UseCase\ImportProfileFromSteamUseCase;
use App\Application\UseCase\ListProfilesUseCase;
use App\Application\UseCase\UpdateStatsAndBroadcastUseCase;
use App\Infrastructure\Broadcast\PusherBroadcaster;
use App\Infrastructure\Persistence\PdoProfileRepository;
use App\Infrastructure\Steam\SteamHttpClient;

require __DIR__ . '/../vendor/autoload.php';

//загрузка переменных из env 
require __DIR__ . '/../config/load-env.php';

//конфигурация бд такая же как у api
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

//инициализация репозитория и юзкейсов
$profileRepository = new PdoProfileRepository($pdo);
$broadcaster = new PusherBroadcaster();
$steamClient = new SteamHttpClient();

$createProfile = new CreateProfileUseCase($profileRepository);
$getProfile = new GetProfileUseCase($profileRepository);
$listProfiles = new ListProfilesUseCase($profileRepository);
$deleteProfile = new DeleteProfileUseCase($profileRepository);
$updateStatsAndBroadcast = new UpdateStatsAndBroadcastUseCase($profileRepository, $broadcaster);
$importFromSteam = new ImportProfileFromSteamUseCase($profileRepository, $steamClient);

$action = $_GET['action'] ?? 'home';
$message = null;
$error = null;

//обработка post действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create_profile') {
        $externalId = (string)($_POST['external_id'] ?? '');
        $nickname = (string)($_POST['nickname'] ?? '');
        $stats = [
            'level' => (int)($_POST['level'] ?? 1),
            'experience' => (int)($_POST['experience'] ?? 0),
            'wins' => (int)($_POST['wins'] ?? 0),
            'losses' => (int)($_POST['losses'] ?? 0),
        ];

        try {
            $profile = $createProfile->execute($externalId, $nickname, $stats);
            $message = 'Профиль создан с ID = ' . $profile->getId();
        } catch (Throwable $e) {
            $error = 'Ошибка создания профиля: ' . $e->getMessage();
        }
    } elseif ($action === 'update_stats') {
        $id = (int)($_POST['id'] ?? 0);
        $stats = [
            'level' => (int)($_POST['level'] ?? 1),
            'experience' => (int)($_POST['experience'] ?? 0),
            'wins' => (int)($_POST['wins'] ?? 0),
            'losses' => (int)($_POST['losses'] ?? 0),
        ];

        try {
            $profile = $updateStatsAndBroadcast->execute($id, $stats);
            $message = 'Статистика обновлена для профиля ID = ' . $profile->getId();
        } catch (Throwable $e) {
            $error = 'Ошибка обновления статистики: ' . $e->getMessage();
        }
    } elseif ($action === 'delete_profile') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $deleteProfile->execute($id);
            $message = 'Профиль удалён, ID = ' . $id;
        } catch (Throwable $e) {
            $error = 'Ошибка удаления профиля: ' . $e->getMessage();
        }
    } elseif ($action === 'import_steam') {
        $apiKey = (string)($_POST['key'] ?? '');
        $steamId = (string)($_POST['steam_id'] ?? '');

        try {
            $profile = $importFromSteam->execute($apiKey, $steamId);
            $message = 'Профиль импортирован/обновлён из Steam. ID = ' . $profile->getId();
        } catch (Throwable $e) {
            $error = 'Ошибка импорта из Steam: ' . $e->getMessage();
        }
    }
}

//получение данных для отображения списка профилей
$profiles = $listProfiles->execute(50, 0);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>КУРСАЧ</title>
</head>
<body>
<h1>Система хранения игровых профилей</h1>

<?php if ($message): ?>
    <p style="color: green;"><?php echo htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
<?php endif; ?>

<?php if ($error): ?>
    <p style="color: red;"><?php echo htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
<?php endif; ?>

<h2>Список профилей</h2>
<table border="1" cellpadding="4" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>External ID</th>
        <th>Ник</th>
        <th>Уровень</th>
        <th>Опыт</th>
        <th>Победы</th>
        <th>Поражения</th>
    </tr>
    <?php foreach ($profiles as $profile): ?>
        <tr>
            <td><?php echo (int)$profile->getId(); ?></td>
            <td><?php echo htmlspecialchars($profile->getExternalId(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($profile->getNickname(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
            <?php $s = $profile->getStats()->toArray(); ?>
            <td><?php echo (int)$s['level']; ?></td>
            <td><?php echo (int)$s['experience']; ?></td>
            <td><?php echo (int)$s['wins']; ?></td>
            <td><?php echo (int)$s['losses']; ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<h2>Создать новый профиль</h2>
<form method="post" action="?action=create_profile">
    <label>External ID (например, SteamID):
        <input type="text" name="external_id" required>
    </label><br>
    <label>Ник:
        <input type="text" name="nickname" required>
    </label><br>
    <label>Уровень:
        <input type="number" name="level" value="1">
    </label><br>
    <label>Опыт:
        <input type="number" name="experience" value="0">
    </label><br>
    <label>Победы:
        <input type="number" name="wins" value="0">
    </label><br>
    <label>Поражения:
        <input type="number" name="losses" value="0">
    </label><br>
    <button type="submit">Создать</button>
    </form>

<h2>Обновить статистику профиля</h2>
<form method="post" action="?action=update_stats">
    <label>ID профиля:
        <input type="number" name="id" required>
    </label><br>
    <label>Уровень:
        <input type="number" name="level" value="1">
    </label><br>
    <label>Опыт:
        <input type="number" name="experience" value="0">
    </label><br>
    <label>Победы:
        <input type="number" name="wins" value="0">
    </label><br>
    <label>Поражения:
        <input type="number" name="losses" value="0">
    </label><br>
    <button type="submit">Обновить статистику</button>
</form>

<h2>Удалить профиль</h2>
<form method="post" action="?action=delete_profile">
    <label>ID профиля:
        <input type="number" name="id" required>
    </label><br>
    <button type="submit">Удалить</button>
</form>

<h2>Импорт профиля из Steam</h2>
<p style="background: #e7f3ff; padding: 10px; border-radius: 4px; margin-bottom: 10px;">
    <strong>Инструкция:</strong><br>
    1. Получи Steam API Key на <a href="https://steamcommunity.com/dev/apikey" target="_blank">steamcommunity.com/dev/apikey</a><br>
    2. Получи свой SteamID64 (длинное число, начинается с 7656119...) через <a href="https://steamid.io/" target="_blank">steamid.io</a> или из URL своего профиля<br>
    3. Заполни форму ниже и нажми "Импортировать"
</p>
<form method="post" action="?action=import_steam">
    <label>Steam API Key:
        <input type="text" name="key" required placeholder="ABC123DEF456..." style="width: 300px;">
        <small style="color: #666;">(Получи на steamcommunity.com/dev/apikey)</small>
    </label><br><br>
    <label>SteamID (SteamID64):
        <input type="text" name="steam_id" required placeholder="76561198012345678" style="width: 300px;">
        <small style="color: #666;">(Длинное число, начинается с 7656119...)</small>
    </label><br><br>
    <button type="submit">Импортировать</button>
</form>

<p>
    <a href="realtime-test.html" target="_blank" >
        Страница синхронизации
    </a>
</p>

</body>
</html>


