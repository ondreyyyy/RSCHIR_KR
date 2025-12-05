<?php

namespace Tests\Unit\UseCase;

use App\Application\UseCase\ListProfilesUseCase;
use App\Domain\Profile;
use App\Domain\ProfileRepositoryInterface;
use App\Domain\Stats;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ListProfilesUseCaseTest extends TestCase
{
    private ListProfilesUseCase $useCase;
    private ProfileRepositoryInterface|MockObject $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProfileRepositoryInterface::class);
        $this->useCase = new ListProfilesUseCase($this->repository);
    }

    public function testExecuteReturnsListOfProfiles(): void
    {
        $profiles = [
            new Profile(1, 'steam_1', 'Player1', new Stats(10, 5000, 25, 15)),
            new Profile(2, 'steam_2', 'Player2', new Stats(15, 8000, 40, 20)),
        ];

        $this->repository
            ->expects($this->once())
            ->method('list')
            ->with(50, 0)
            ->willReturn($profiles);

        $result = $this->useCase->execute();

        $this->assertCount(2, $result);
        $this->assertSame($profiles, $result);
    }

    public function testExecuteRespectsLimitAndOffset(): void
    {
        $limit = 10;
        $offset = 5;
        $profiles = [];

        $this->repository
            ->expects($this->once())
            ->method('list')
            ->with($limit, $offset)
            ->willReturn($profiles);

        $result = $this->useCase->execute($limit, $offset);

        $this->assertSame($profiles, $result);
    }

    public function testExecuteValidatesPaginationParams(): void
    {
        //тест на валидацию limit (должен быть минимум 1)
        $this->repository
            ->expects($this->once())
            ->method('list')
            ->with(1, 0) // limit должен быть исправлен на 1
            ->willReturn([]);

        $this->useCase->execute(0, 0);
    }
}

