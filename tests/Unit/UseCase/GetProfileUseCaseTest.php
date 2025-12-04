<?php

namespace Tests\Unit\UseCase;

use App\Application\UseCase\GetProfileUseCase;
use App\Domain\Profile;
use App\Domain\ProfileRepositoryInterface;
use App\Domain\Stats;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class GetProfileUseCaseTest extends TestCase
{
    private GetProfileUseCase $useCase;
    private ProfileRepositoryInterface|MockObject $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProfileRepositoryInterface::class);
        $this->useCase = new GetProfileUseCase($this->repository);
    }

    public function testExecuteReturnsProfileWhenFound(): void
    {
        $profileId = 1;
        $expectedProfile = new Profile(
            id: $profileId,
            externalId: 'steam_76561198012345678',
            nickname: 'TestPlayer',
            stats: new Stats(10, 5000, 25, 15)
        );

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with($profileId)
            ->willReturn($expectedProfile);

        $result = $this->useCase->execute($profileId);

        $this->assertSame($expectedProfile, $result);
    }

    public function testExecuteReturnsNullWhenProfileNotFound(): void
    {
        $profileId = 999;

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with($profileId)
            ->willReturn(null);

        $result = $this->useCase->execute($profileId);

        $this->assertNull($result);
    }

    public function testExecuteValidatesProfileId(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->useCase->execute(0);
    }
}

