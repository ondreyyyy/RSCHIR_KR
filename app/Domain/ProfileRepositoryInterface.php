<?php

namespace App\Domain;

/**
 * Репозиторий как абстракция хранилища профилей.
 * Инфраструктурный слой будет реализовывать этот интерфейс
 * для PostgreSQL.
 */
interface ProfileRepositoryInterface
{
    public function create(Profile $profile): Profile;

    public function update(Profile $profile): Profile;

    public function delete(int $id): void;

    public function findById(int $id): ?Profile;

    public function findByExternalId(string $externalId): ?Profile;

    /**
     * Примитивный листинг профилей с пагинацией.
     *
     * @return Profile[]
     */
     public function list(int $limit = 50, int $offset = 0): array;
}


