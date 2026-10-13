<?php

declare(strict_types=1);

namespace DialecticPlayground\Hello\Tests;

use DialecticPlayground\Hello\Action;
use DialecticPlayground\Hello\ArgumentParser;
use DialecticPlayground\Hello\OptionValue;
use DialecticPlayground\Hello\ParsedArguments;
use DialecticPlayground\Hello\UsageError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ArgumentParserTest extends TestCase
{
    public function testNoArgumentsMeansDefaultGreeting(): void
    {
        self::assertEquals(new ParsedArguments(), ArgumentParser::parse([]));
    }

    /**
     * @param list<string> $args
     */
    #[DataProvider('provideNameSpellingsCases')]
    public function testNameSpellings(array $args, string $value, string $spelling): void
    {
        self::assertEquals(new OptionValue($value, $spelling), ArgumentParser::parse($args)->name);
    }

    /**
     * @return iterable<string, array{list<string>, string, string}>
     */
    public static function provideNameSpellingsCases(): iterable
    {
        yield 'short separate' => [['-n', 'Ada'], 'Ada', '-n'];

        yield 'short attached' => [['-nAda'], 'Ada', '-n'];

        yield 'long separate' => [['--name', 'Ada Lovelace'], 'Ada Lovelace', '--name'];

        yield 'long attached' => [['--name=Ada'], 'Ada', '--name'];

        yield 'long attached empty' => [['--name='], '', '--name'];

        yield 'long attached with =' => [['--name=a=b'], 'a=b', '--name'];

        yield 'long attached dash' => [['--name=-x'], '-x', '--name'];

        yield 'short attached dash' => [['-n-x'], '-x', '-n'];

        yield 'long separate dash' => [['--name', '-x'], '-x', '--name'];

        yield 'short separate double dash' => [['-n', '--'], '--', '-n'];

        yield 'separate empty' => [['-n', ''], '', '-n'];

        yield 'non-ASCII' => [['-n', 'Zoë'], 'Zoë', '-n'];
    }

    public function testBundledShortOptionsTakeTheRestOfTheToken(): void
    {
        $parsed = ArgumentParser::parse(['-c2', '-nKen']);
        self::assertEquals(new OptionValue('2', '-c'), $parsed->count);
        self::assertEquals(new OptionValue('Ken', '-n'), $parsed->name);
        self::assertEquals(new OptionValue('n5', '-c'), ArgumentParser::parse(['-cn5'])->count);
    }

    public function testLastOccurrenceWins(): void
    {
        $parsed = ArgumentParser::parse(['-n', 'A', '--name=B', '-c', '0', '--count', '3']);
        self::assertEquals(new OptionValue('B', '--name'), $parsed->name);
        self::assertEquals(new OptionValue('3', '--count'), $parsed->count);
    }

    public function testOperandsAreRecordedNotRejected(): void
    {
        self::assertSame('extra', ArgumentParser::parse(['extra', 'more'])->firstOperand);
        self::assertSame('-', ArgumentParser::parse(['-'])->firstOperand);
        self::assertSame('x', ArgumentParser::parse(['-n', 'A', 'x', '-c', '2'])->firstOperand);
    }

    public function testDoubleDashTurnsEverythingAfterItIntoOperands(): void
    {
        $parsed = ArgumentParser::parse(['--', '--help', '-n']);
        self::assertSame(Action::Greet, $parsed->action);
        self::assertSame('--help', $parsed->firstOperand);
        self::assertNull(ArgumentParser::parse(['-n', 'A', '--'])->firstOperand);
    }

    /**
     * @param list<string> $args
     */
    #[DataProvider('provideHelpAndVersionEndTheScanCases')]
    public function testHelpAndVersionEndTheScan(array $args, Action $action): void
    {
        self::assertSame($action, ArgumentParser::parse($args)->action);
    }

    /**
     * @return iterable<string, array{list<string>, Action}>
     */
    public static function provideHelpAndVersionEndTheScanCases(): iterable
    {
        yield 'help' => [['--help'], Action::Help];

        yield 'version' => [['--version'], Action::Version];

        yield 'help before syntax error' => [['--help', 'extra', '-x'], Action::Help];

        yield 'help after operand' => [['extra', '--help'], Action::Help];

        yield 'help after invalid value' => [['--count', '0', '--help'], Action::Help];

        yield 'first of help and version wins' => [['--version', '--help'], Action::Version];

        yield 'help after empty name' => [['--name=', '--help'], Action::Help];
    }

    /**
     * @param list<string> $args
     */
    #[DataProvider('provideSyntaxErrorsCases')]
    public function testSyntaxErrors(array $args, string $message): void
    {
        $this->expectException(UsageError::class);
        $this->expectExceptionMessage($message);
        ArgumentParser::parse($args);
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function provideSyntaxErrorsCases(): iterable
    {
        yield 'unknown short' => [['-x'], "invalid option -- 'x'"];

        yield 'unknown short non-ASCII' => [['-ë'], "invalid option -- 'ë'"];

        yield 'unknown short before help' => [['-x', '--help'], "invalid option -- 'x'"];

        yield 'unknown long' => [['--nme', 'Ada'], "unrecognized option '--nme'"];

        yield 'abbreviated long' => [['--nam=Ada'], "unrecognized option '--nam=Ada'"];

        yield 'long prefix of help' => [['--he'], "unrecognized option '--he'"];

        yield 'triple dash' => [['---name'], "unrecognized option '---name'"];

        yield 'missing short argument n' => [['-n'], "option requires an argument -- 'n'"];

        yield 'missing short argument c' => [['-c'], "option requires an argument -- 'c'"];

        yield 'missing long argument name' => [['--name'], "option '--name' requires an argument"];

        yield 'missing long argument count' => [['--count'], "option '--count' requires an argument"];

        yield 'argument to help' => [['--help=1'], "option '--help' doesn't allow an argument"];

        yield 'empty argument to version' => [['--version='], "option '--version' doesn't allow an argument"];
    }
}
