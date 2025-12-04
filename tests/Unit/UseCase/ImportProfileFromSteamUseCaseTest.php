<?php

namespace Tests\Unit\UseCase;

use App\Application\Ports\SteamClientInterface;
use App\Application\UseCase\ImportProfileFromSteamUseCase;
use App\Domain\Profile;
use App\Domain\ProfileRepositoryInterface;
use App\Domain\Stats;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ImportProfileFromSteamUseCaseTest extends TestCase
{
    private ImportProfileFromSteamUseCase $useCase;
    private ProfileRepositoryInterface|MockObject $repository;
    private SteamClientInterface|MockObject $steamClient;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProfileRepositoryInterface::class);
        $this->steamClient = $this->createMock(SteamClientInterface::class);
        $this->useCase = new ImportProfileFromSteamUseCase($this->repository, $this->steamClient);
    }

    public function testExecuteCreatesNewProfileWhenNotFound(): void
    {
        $apiKey = 'test-api-key';
        $steamId = '76561198012345678';
        $steamData = [
            'external_id' => $steamId,
            'nickname' => 'SteamPlayer',
        ];

        $this->steamClient
            ->expects($this->once())
            ->method('fetchProfile')
            ->with($apiKey, $steamId)
            ->willReturn($steamData);

        $this->repository
            ->expects($this->once())
            ->method('findByExternalId')
            ->with($steamId)
            ->willReturn(null);

        $createdProfile = new Profile(
            id: 1,
            externalId: $steamId,
            nickname: 'SteamPlayer',
            stats: Stats::fromArray([])
        );
        $createdProfile->setId(1);

        $this->repository
            ->expects($this->once())
            ->method('create')
            ->willReturn($createdProfile);

        $result = $this->useCase->execute($apiKey, $steamId);

        $this->assertInstanceOf(Profile::class, $result);
        $this->assertEquals($steamId, $result->getExternalId());
    }

    public function testExecuteUpdatesExistingProfile(): void
    {
        $apiKey = 'test-api-key';
        $steamId = '76561198012345678';
        $steamData = [
            'external_id' => $steamId,
            'nickname' => 'UpdatedNickname',
        ];

        $existingProfile = new Profile(
            id: 1,
            externalId: $steamId,
            nickname: 'OldNickname',
            stats: Stats::fromArray([])
        );

        $this->steamClient
            ->expects($this->once())
            ->method('fetchProfile')
            ->with($apiKey, $steamId)
            ->willReturn($steamData);

        $this->repository
            ->expects($this->once())
            ->method('findByExternalId')
            ->with($steamId)
            ->willReturn($existingProfile);

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->willReturn($existingProfile);

        $this->repository
            ->expects($this->never())
            ->method('create');

        $result = $this->useCase->execute($apiKey, $steamId);

        $this->assertInstanceOf(Profile::class, $result);
    }

    public function testExecuteValidatesSteamApiKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->useCase->execute('', '76561198012345678');
    }

    public function testExecuteValidatesSteamId(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->useCase->execute('valid-key', 'invalid-steam-id');
    }
}

