<?php

declare(strict_types=1);

namespace DialecticPlayground\Hello\Tests;

use DialecticPlayground\Hello\Outcome;
use DialecticPlayground\Hello\Output;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class OutputTest extends TestCase
{
    public function testEmitWritesTheExactBytesAndReturnsTheStatus(): void
    {
        $stdout = self::memoryStream();
        $stderr = self::memoryStream();

        self::assertSame(2, Output::emit(new Outcome("a\nb\n", "Zoë\n", 2), $stdout, $stderr));
        self::assertSame("a\nb\n", self::contents($stdout));
        self::assertSame("Zoë\n", self::contents($stderr));
    }

    public function testWriteToAnUnwritableStreamFailsQuietly(): void
    {
        $readOnly = fopen('php://memory', 'r');
        self::assertIsResource($readOnly);

        self::assertFalse(Output::write($readOnly, "Hello, World!\n"));
    }

    /**
     * @return resource
     */
    private static function memoryStream()
    {
        $stream = fopen('php://memory', 'w+');
        self::assertIsResource($stream);

        return $stream;
    }

    /**
     * @param resource $stream
     */
    private static function contents($stream): string
    {
        rewind($stream);
        $contents = stream_get_contents($stream);
        self::assertIsString($contents);

        return $contents;
    }
}
