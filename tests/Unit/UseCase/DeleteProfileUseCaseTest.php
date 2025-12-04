<?php

namespace Tests\Unit\UseCase;

use App\Application\UseCase\DeleteProfileUseCase;
use App\Domain\Profile;
use App\Domain\ProfileRepositoryInterface;
use App\Domain\Stats;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class DeleteProfileUseCaseTest extends TestCase
{
    private DeleteProfileUseCase $useCase;
    private ProfileRepositoryInterface|MockObject $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProfileRepositoryInterface::class);
        $this->useCase = new DeleteProfileUseCase($this->repository);
    }

    public function testExecuteDeletesProfileSuccessfully(): void
    {
        $profileId = 1;
        $profile = new Profile(
            id: $profileId,
            externalId: 'steam_76561198012345678',
            nickname: 'TestPlayer',
            stats: new Stats(10, 5000, 25, 15)
        );

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with($profileId)
            ->willReturn($profile);

        $this->repository
            ->expects($this->once())
            ->method('delete')
            ->with($profileId);

        $this->useCase->execute($profileId);
    }

    public function testExecuteThrowsExceptionWhenProfileNotFound(): void
    {
        $profileId = 999;

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with($profileId)
            ->willReturn(null);

        $this->repository
            ->expects($this->never())
            ->method('delete');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Profile not found');

        $this->useCase->execute($profileId);
    }

    public function testExecuteValidatesProfileId(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->useCase->execute(-1);
    }
}

