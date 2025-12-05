<?php

namespace Tests\Integration;

use App\Domain\Profile;
use App\Domain\Stats;
use App\Infrastructure\Persistence\PdoProfileRepository;
use PDO;
use PHPUnit\Framework\TestCase;

class PdoProfileRepositoryTest extends TestCase
{
    private PDO $pdo;
    private PdoProfileRepository $repository;

    protected function setUp(): void
    {
        // использование тестовой бд или inmemory sqlite для тестов
        $dsn = getenv('TEST_DB_DSN') ?: 'sqlite::memory:';
        $this->pdo = new PDO($dsn);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // создание таблицы для тестов
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS profiles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                external_id VARCHAR(64) NOT NULL UNIQUE,
                nickname VARCHAR(255) NOT NULL,
                stats_json TEXT NOT NULL DEFAULT "{}",
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ');

        $this->repository = new PdoProfileRepository($this->pdo);
    }

    protected function tearDown(): void
    {
        // очистка таблицы после каждого теста
        $this->pdo->exec('DELETE FROM profiles');
    }

    public function testCreateProfile(): void
    {
        $profile = new Profile(
            id: null,
            externalId: 'steam_76561198012345678',
            nickname: 'TestPlayer',
            stats: new Stats(10, 5000, 25, 15)
        );

        $created = $this->repository->create($profile);

        $this->assertNotNull($created->getId());
        $this->assertEquals('steam_76561198012345678', $created->getExternalId());
        $this->assertEquals('TestPlayer', $created->getNickname());
    }

    public function testFindByIdReturnsProfile(): void
    {
        $profile = new Profile(
            id: null,
            externalId: 'steam_76561198012345678',
            nickname: 'TestPlayer',
            stats: new Stats(10, 5000, 25, 15)
        );

        $created = $this->repository->create($profile);
        $found = $this->repository->findById($created->getId());

        $this->assertNotNull($found);
        $this->assertEquals($created->getId(), $found->getId());
        $this->assertEquals('TestPlayer', $found->getNickname());
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $found = $this->repository->findById(999);
        $this->assertNull($found);
    }

    public function testFindByExternalIdReturnsProfile(): void
    {
        $profile = new Profile(
            id: null,
            externalId: 'steam_76561198012345678',
            nickname: 'TestPlayer',
            stats: new Stats(10, 5000, 25, 15)
        );

        $this->repository->create($profile);
        $found = $this->repository->findByExternalId('steam_76561198012345678');

        $this->assertNotNull($found);
        $this->assertEquals('steam_76561198012345678', $found->getExternalId());
    }

    public function testUpdateProfile(): void
    {
        $profile = new Profile(
            id: null,
            externalId: 'steam_76561198012345678',
            nickname: 'TestPlayer',
            stats: new Stats(10, 5000, 25, 15)
        );

        $created = $this->repository->create($profile);
        $created->setNickname('UpdatedPlayer');
        $created->setStats(new Stats(20, 10000, 50, 30));

        $updated = $this->repository->update($created);

        $this->assertEquals('UpdatedPlayer', $updated->getNickname());
        $this->assertEquals(20, $updated->getStats()->getLevel());
    }

    public function testDeleteProfile(): void
    {
        $profile = new Profile(
            id: null,
            externalId: 'steam_76561198012345678',
            nickname: 'TestPlayer',
            stats: new Stats(10, 5000, 25, 15)
        );

        $created = $this->repository->create($profile);
        $this->repository->delete($created->getId());

        $found = $this->repository->findById($created->getId());
        $this->assertNull($found);
    }

    public function testListProfiles(): void
    {
        $profile1 = new Profile(null, 'steam_1', 'Player1', new Stats(10, 5000, 25, 15));
        $profile2 = new Profile(null, 'steam_2', 'Player2', new Stats(15, 8000, 40, 20));

        $this->repository->create($profile1);
        $this->repository->create($profile2);

        $profiles = $this->repository->list(10, 0);

        $this->assertCount(2, $profiles);
    }

    public function testListProfilesRespectsLimit(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $profile = new Profile(null, "steam_$i", "Player$i", new Stats(10, 5000, 25, 15));
            $this->repository->create($profile);
        }

        $profiles = $this->repository->list(3, 0);
        $this->assertCount(3, $profiles);
    }

    public function testPreparedStatementsProtectAgainstSqlInjection(): void
    {
        // попытка sql инъекции через external_id
        $maliciousId = "'; DROP TABLE profiles; --";
        
        try {
            $profile = new Profile(
                id: null,
                externalId: $maliciousId,
                nickname: 'Test',
                stats: new Stats(1, 0, 0, 0)
            );
            
            $this->repository->create($profile);
            
            // если таблица все еще существует - инъекция не сработала
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM profiles");
            $count = $stmt->fetchColumn();
            
            $this->assertIsNumeric($count);
        } catch (\Exception $e) {
            // ожидание ошибки валидации но не инъекцию
            $this->assertStringNotContainsString('DROP TABLE', $e->getMessage());
        }
    }
}

