<?php

declare(strict_types=1);

namespace DialecticPlayground\Hello\Tests;

use DialecticPlayground\Hello\Config;
use DialecticPlayground\Hello\OptionValue;
use DialecticPlayground\Hello\ParsedArguments;
use DialecticPlayground\Hello\UsageError;
use DialecticPlayground\Hello\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ValidatorTest extends TestCase
{
    public function testDefaults(): void
    {
        self::assertEquals(new Config('World', 1), Validator::validate(new ParsedArguments()));
    }

    public function testNameIsUsedVerbatim(): void
    {
        self::assertSame(' Zoë - x! ', Validator::name(new OptionValue(' Zoë - x! ', '-n')));
    }

    public function testEmptyNameIsRejectedWithTheOptionAsWritten(): void
    {
        $this->expectException(UsageError::class);
        $this->expectExceptionMessage("invalid argument '' for '--name'");
        Validator::name(new OptionValue('', '--name'));
    }

    #[DataProvider('provideValidCountsCases')]
    public function testValidCounts(string $text, int $expected): void
    {
        self::assertSame($expected, Validator::count(new OptionValue($text, '-c')));
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function provideValidCountsCases(): iterable
    {
        yield 'lower bound' => ['1', 1];

        yield 'upper bound' => ['1000', 1000];

        yield 'leading zeros' => ['007', 7];

        yield 'many leading zeros' => ['0000000000000000000001000', 1000];
    }

    #[DataProvider('provideInvalidCountsCases')]
    public function testInvalidCounts(string $text): void
    {
        $this->expectException(UsageError::class);
        $this->expectExceptionMessage("invalid argument '{$text}' for '--count'");
        Validator::count(new OptionValue($text, '--count'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidCountsCases(): iterable
    {
        yield 'zero' => ['0'];

        yield 'zeros' => ['000'];

        yield 'above upper bound' => ['1001'];

        yield 'leading zeros above upper bound' => ['01001'];

        yield 'huge' => ['99999999999999999999999999'];

        yield 'empty' => [''];

        yield 'letters' => ['abc'];

        yield 'sign' => ['+5'];

        yield 'negative' => ['-1'];

        yield 'space' => [' 5'];

        yield 'trailing newline' => ["5\n"];

        yield 'decimal' => ['2.0'];

        yield 'non-ASCII digit' => ['５'];
    }

    public function testOperandIsReportedBeforeInvalidValues(): void
    {
        $this->expectException(UsageError::class);
        $this->expectExceptionMessage("extra operand 'x'");
        Validator::validate(new ParsedArguments(name: new OptionValue('', '-n'), firstOperand: 'x'));
    }

    public function testNameIsCheckedBeforeCount(): void
    {
        $this->expectException(UsageError::class);
        $this->expectExceptionMessage("invalid argument '' for '-n'");
        Validator::validate(new ParsedArguments(
            name: new OptionValue('', '-n'),
            count: new OptionValue('0', '-c'),
        ));
    }

    public function testValidRequest(): void
    {
        $parsed = new ParsedArguments(name: new OptionValue('Ken', '-n'), count: new OptionValue('2', '-c'));
        self::assertEquals(new Config('Ken', 2), Validator::validate($parsed));
    }
}
