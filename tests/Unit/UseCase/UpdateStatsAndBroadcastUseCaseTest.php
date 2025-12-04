<?php

namespace Tests\Unit\UseCase;

use App\Application\Ports\BroadcasterInterface;
use App\Application\UseCase\UpdateStatsAndBroadcastUseCase;
use App\Domain\Profile;
use App\Domain\ProfileRepositoryInterface;
use App\Domain\Stats;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class UpdateStatsAndBroadcastUseCaseTest extends TestCase
{
    private UpdateStatsAndBroadcastUseCase $useCase;
    private ProfileRepositoryInterface|MockObject $repository;
    private BroadcasterInterface|MockObject $broadcaster;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProfileRepositoryInterface::class);
        $this->broadcaster = $this->createMock(BroadcasterInterface::class);
        $this->useCase = new UpdateStatsAndBroadcastUseCase($this->repository, $this->broadcaster);
    }

    public function testExecuteUpdatesStatsAndBroadcasts(): void
    {
        $profileId = 1;
        $newStats = [
            'level' => 20,
            'experience' => 10000,
            'wins' => 50,
            'losses' => 30,
        ];

        $existingProfile = new Profile(
            id: $profileId,
            externalId: 'steam_76561198012345678',
            nickname: 'TestPlayer',
            stats: new Stats(10, 5000, 25, 15)
        );

        $updatedProfile = new Profile(
            id: $profileId,
            externalId: 'steam_76561198012345678',
            nickname: 'TestPlayer',
            stats: Stats::fromArray($newStats)
        );

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with($profileId)
            ->willReturn($existingProfile);

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->willReturn($updatedProfile);

        $this->broadcaster
            ->expects($this->once())
            ->method('broadcast')
            ->with(
                'profiles',
                'stats.updated',
                $this->callback(function (array $payload) use ($profileId) {
                    return $payload['id'] === $profileId
                        && isset($payload['stats'])
                        && $payload['stats']['level'] === 20;
                })
            );

        $result = $this->useCase->execute($profileId, $newStats);

        $this->assertInstanceOf(Profile::class, $result);
        $this->assertEquals(20, $result->getStats()->getLevel());
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
            ->method('update');

        $this->broadcaster
            ->expects($this->never())
            ->method('broadcast');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Profile not found');

        $this->useCase->execute($profileId, []);
    }
}

