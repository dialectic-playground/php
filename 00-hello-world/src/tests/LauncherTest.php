<?php

declare(strict_types=1);

namespace DialecticPlayground\Hello\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Runs the real launcher in a child process: UTF-8 arguments reach $argv intact and the
 * output bytes are LF-only on every operating system (CONV-03, CONV-05).
 *
 * @internal
 *
 * @coversNothing
 */
final class LauncherTest extends TestCase
{
    public function testUtf8NameAndLfOnlyOutput(): void
    {
        [$stdout, $stderr, $status] = self::runLauncher(['-c2', '--name', "Zo\u{eb}"]);

        self::assertSame("Hello, Zo\u{eb}!\nHello, Zo\u{eb}!\n", $stdout);
        self::assertSame('', $stderr);
        self::assertSame(0, $status);
    }

    public function testUsageErrorGoesToStandardError(): void
    {
        [$stdout, $stderr, $status] = self::runLauncher(['-x']);

        self::assertSame('', $stdout);
        self::assertSame("dp-hello: invalid option -- 'x'\nTry 'dp-hello --help' for more information.\n", $stderr);
        self::assertSame(2, $status);
    }

    /**
     * @param list<string> $args
     *
     * @return array{string, string, int}
     */
    private static function runLauncher(array $args): array
    {
        $command = [PHP_BINARY, \dirname(__DIR__).'/bin/dp-hello', ...$args];
        $pipes = [];
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($process);
        self::assertIsString($stdout);
        self::assertIsString($stderr);

        return [$stdout, $stderr, $status];
    }
}
