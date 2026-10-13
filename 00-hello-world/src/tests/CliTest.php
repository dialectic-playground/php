<?php

declare(strict_types=1);

namespace DialecticPlayground\Hello\Tests;

use DialecticPlayground\Hello\Cli;
use DialecticPlayground\Hello\Outcome;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class CliTest extends TestCase
{
    private const string HELP = "Usage: dp-hello [OPTION]...\n"
        ."Print a greeting to standard output.\n"
        ."\n"
        ."Options:\n"
        ."  -n, --name=NAME     greet NAME instead of World\n"
        ."  -c, --count=COUNT   print the greeting COUNT times (1-1000, default 1)\n"
        ."      --help          display this help and exit\n"
        ."      --version       output version information and exit\n";

    /**
     * @param list<string> $args
     */
    #[DataProvider('provideSuccessCases')]
    public function testSuccess(array $args, string $stdout): void
    {
        self::assertEquals(new Outcome($stdout, '', 0), Cli::run($args));
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function provideSuccessCases(): iterable
    {
        yield 'default' => [[], "Hello, World!\n"];

        yield 'named' => [['--name', 'Ada'], "Hello, Ada!\n"];

        yield 'UTF-8 name' => [['-n', 'Zoë'], "Hello, Zoë!\n"];

        yield 'count and name' => [['-c2', '-n', 'Ken'], "Hello, Ken!\nHello, Ken!\n"];

        yield 'last wins' => [['-n', 'A', '-n', 'B'], "Hello, B!\n"];

        yield 'invalid value overridden' => [['-c', '0', '-c', '1'], "Hello, World!\n"];

        yield 'help' => [['--help'], self::HELP];

        yield 'help after operand' => [['extra', '--help'], self::HELP];

        yield 'help after invalid count' => [['--count', '0', '--help'], self::HELP];

        yield 'help before syntax error' => [['--help', 'extra', '-x'], self::HELP];

        yield 'version' => [['--version'], "dp-hello (dialectic-playground) 1.0.0\n"];
    }

    /**
     * @param list<string> $args
     */
    #[DataProvider('provideUsageErrorsWriteOnlyTheDiagnosticCases')]
    public function testUsageErrorsWriteOnlyTheDiagnostic(array $args, string $message): void
    {
        $expected = "dp-hello: {$message}\nTry 'dp-hello --help' for more information.\n";
        self::assertEquals(new Outcome('', $expected, 2), Cli::run($args));
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function provideUsageErrorsWriteOnlyTheDiagnosticCases(): iterable
    {
        yield 'invalid option' => [['-x'], "invalid option -- 'x'"];

        yield 'invalid option before help' => [['-x', '--help'], "invalid option -- 'x'"];

        yield 'unrecognized option' => [['--nme', 'Ada'], "unrecognized option '--nme'"];

        yield 'missing argument' => [['--name'], "option '--name' requires an argument"];

        yield 'argument to flag' => [['--version=1'], "option '--version' doesn't allow an argument"];

        yield 'empty name' => [['--name', ''], "invalid argument '' for '--name'"];

        yield 'zero count' => [['--count', '0'], "invalid argument '0' for '--count'"];

        yield 'letters count' => [['-c', 'abc'], "invalid argument 'abc' for '-c'"];

        yield 'count too large' => [['-c', '1001'], "invalid argument '1001' for '-c'"];

        yield 'extra operand' => [['extra'], "extra operand 'extra'"];

        yield 'operand after terminator' => [['--', '-n'], "extra operand '-n'"];
    }
}
