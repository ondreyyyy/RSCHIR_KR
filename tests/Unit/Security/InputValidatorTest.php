<?php

namespace Tests\Unit\Security;

use App\Infrastructure\Security\InputValidator;
use PHPUnit\Framework\TestCase;

class InputValidatorTest extends TestCase
{
    public function testSanitizeStringRemovesHtmlTags(): void
    {
        $input = '<script>alert("xss")</script>Hello';
        $result = InputValidator::sanitizeString($input);

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('</script>', $result);
    }

    public function testSanitizeStringEscapesSpecialCharacters(): void
    {
        $input = 'Test "quotes" & <tags>';
        $result = InputValidator::sanitizeString($input);

        $this->assertStringNotContainsString('<tags>', $result);
        $this->assertStringNotContainsString('"quotes"', $result);
    }

    public function testValidateExternalIdAcceptsValidId(): void
    {
        $result = InputValidator::validateExternalId('steam_76561198012345678');
        $this->assertEquals('steam_76561198012345678', $result);
    }

    public function testValidateExternalIdThrowsExceptionForEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        InputValidator::validateExternalId('');
    }

    public function testValidateExternalIdThrowsExceptionForInvalidCharacters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        InputValidator::validateExternalId('invalid@id#123');
    }

    public function testValidateNicknameSanitizesXss(): void
    {
        $result = InputValidator::validateNickname('<script>alert("xss")</script>Player');
        $this->assertStringNotContainsString('<script>', $result);
    }

    public function testValidateProfileIdThrowsExceptionForZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        InputValidator::validateProfileId(0);
    }

    public function testValidateProfileIdThrowsExceptionForNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        InputValidator::validateProfileId(-1);
    }

    public function testValidateStatsNormalizesValues(): void
    {
        $stats = [
            'level' => 10,
            'experience' => 5000,
            'wins' => 25,
            'losses' => 15,
        ];

        $result = InputValidator::validateStats($stats);

        $this->assertEquals(10, $result['level']);
        $this->assertEquals(5000, $result['experience']);
        $this->assertEquals(25, $result['wins']);
        $this->assertEquals(15, $result['losses']);
    }

    public function testValidateStatsUsesDefaults(): void
    {
        $result = InputValidator::validateStats([]);

        $this->assertEquals(1, $result['level']);
        $this->assertEquals(0, $result['experience']);
        $this->assertEquals(0, $result['wins']);
        $this->assertEquals(0, $result['losses']);
    }

    public function testValidateSteamIdAcceptsValidSteamId64(): void
    {
        $result = InputValidator::validateSteamId('76561198012345678');
        $this->assertEquals('76561198012345678', $result);
    }

    public function testValidateSteamIdThrowsExceptionForInvalidFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        InputValidator::validateSteamId('invalid-steam-id');
    }

    public function testValidatePaginationParamsNormalizesValues(): void
    {
        [$limit, $offset] = InputValidator::validatePaginationParams(10, 5);
        $this->assertEquals(10, $limit);
        $this->assertEquals(5, $offset);
    }

    public function testValidatePaginationParamsEnforcesMinimumLimit(): void
    {
        [$limit, $offset] = InputValidator::validatePaginationParams(0, 0);
        $this->assertEquals(1, $limit);
    }
}

