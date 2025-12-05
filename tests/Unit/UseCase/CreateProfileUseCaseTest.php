<?php

namespace Tests\Unit\UseCase;

use App\Application\UseCase\CreateProfileUseCase;
use App\Domain\Profile;
use App\Domain\ProfileRepositoryInterface;
use App\Domain\Stats;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CreateProfileUseCaseTest extends TestCase
{
    private CreateProfileUseCase $useCase;
    private ProfileRepositoryInterface|MockObject $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProfileRepositoryInterface::class);
        $this->useCase = new CreateProfileUseCase($this->repository);
    }

    public function testExecuteCreatesProfileSuccessfully(): void
    {
        $externalId = 'steam_76561198012345678';
        $nickname = 'TestPlayer';
        $statsData = [
            'level' => 10,
            'experience' => 5000,
            'wins' => 25,
            'losses' => 15,
        ];

        $expectedProfile = new Profile(
            id: null,
            externalId: $externalId,
            nickname: $nickname,
            stats: Stats::fromArray($statsData)
        );

        $savedProfile = new Profile(
            id: 1,
            externalId: $externalId,
            nickname: $nickname,
            stats: Stats::fromArray($statsData)
        );
        $savedProfile->setId(1);

        $this->repository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function (Profile $profile) use ($externalId, $nickname) {
                return $profile->getExternalId() === $externalId
                    && $profile->getNickname() === $nickname;
            }))
            ->willReturn($savedProfile);

        $result = $this->useCase->execute($externalId, $nickname, $statsData);

        $this->assertInstanceOf(Profile::class, $result);
        $this->assertEquals(1, $result->getId());
        $this->assertEquals($externalId, $result->getExternalId());
        $this->assertEquals($nickname, $result->getNickname());
    }

    public function testExecuteValidatesAndSanitizesInput(): void
    {
        $externalId = 'steam_76561198012345678';
        $nickname = '<script>alert("xss")</script>TestPlayer';
        $statsData = ['level' => 5];

        $savedProfile = new Profile(
            id: 1,
            externalId: $externalId,
            nickname: htmlspecialchars(strip_tags($nickname), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            stats: Stats::fromArray(['level' => 5, 'experience' => 0, 'wins' => 0, 'losses' => 0])
        );
        $savedProfile->setId(1);

        $this->repository
            ->expects($this->once())
            ->method('create')
            ->willReturn($savedProfile);

        $result = $this->useCase->execute($externalId, $nickname, $statsData);

        //проверка что xss был удален
        $this->assertStringNotContainsString('<script>', $result->getNickname());
    }

    public function testExecuteThrowsExceptionForInvalidExternalId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('External ID cannot be empty');

        $this->useCase->execute('', 'TestPlayer', []);
    }

    public function testExecuteThrowsExceptionForInvalidNickname(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Nickname cannot be empty');

        $this->useCase->execute('steam_123', '', []);
    }
}

