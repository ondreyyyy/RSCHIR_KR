<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Profile;
use App\Domain\ProfileRepositoryInterface;
use App\Domain\Stats;
use PDO;

// postgresql реализация через pdo
class PdoProfileRepository implements ProfileRepositoryInterface
{
    public function __construct(
        private PDO $connection
    ) {
    }

    public function create(Profile $profile): Profile
    {
        $sql = 'INSERT INTO profiles (external_id, nickname, stats_json)
                VALUES (:external_id, :nickname, :stats_json)
                RETURNING id';

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            'external_id' => $profile->getExternalId(),
            'nickname' => $profile->getNickname(),
            'stats_json' => json_encode($profile->getStats()->toArray(), JSON_THROW_ON_ERROR),
        ]);

        $id = (int) $stmt->fetchColumn();
        $profile->setId($id);

        return $profile;
    }

    public function update(Profile $profile): Profile
    {
        $sql = 'UPDATE profiles
                SET external_id = :external_id,
                    nickname = :nickname,
                    stats_json = :stats_json,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id';

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            'id' => $profile->getId(),
            'external_id' => $profile->getExternalId(),
            'nickname' => $profile->getNickname(),
            'stats_json' => json_encode($profile->getStats()->toArray(), JSON_THROW_ON_ERROR),
        ]);

        return $profile;
    }

    public function delete(int $id): void
    {
        $stmt = $this->connection->prepare('DELETE FROM profiles WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function findById(int $id): ?Profile
    {
        $stmt = $this->connection->prepare('SELECT * FROM profiles WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->mapRowToProfile($row);
    }

    public function findByExternalId(string $externalId): ?Profile
    {
        $stmt = $this->connection->prepare('SELECT * FROM profiles WHERE external_id = :external_id');
        $stmt->execute(['external_id' => $externalId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->mapRowToProfile($row);
    }

    public function list(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->connection->prepare(
            'SELECT * FROM profiles ORDER BY id LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $profiles = [];

        foreach ($rows as $row) {
            $profiles[] = $this->mapRowToProfile($row);
        }

        return $profiles;
    }

    private function mapRowToProfile(array $row): Profile
    {
        $statsArray = json_decode($row['stats_json'] ?? '{}', true, 512, JSON_THROW_ON_ERROR);
        $stats = Stats::fromArray($statsArray);

        return new Profile(
            id: (int) $row['id'],
            externalId: $row['external_id'],
            nickname: $row['nickname'],
            stats: $stats
        );
    }
}


