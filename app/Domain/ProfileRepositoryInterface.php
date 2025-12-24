<?php

namespace App\Domain;

//репозиторий как абстракция хранилища профилей

interface ProfileRepositoryInterface
{
    public function create(Profile $profile): Profile;

    public function update(Profile $profile): Profile;

    public function delete(int $id): void;

    public function findById(int $id): ?Profile;

    public function findByExternalId(string $externalId): ?Profile;

    //листинг профилей с пагинацией
     public function list(int $limit = 50, int $offset = 0): array;
}


