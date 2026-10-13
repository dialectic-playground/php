<?php

declare(strict_types=1);

namespace DialecticPlayground\Hello\Tests;

use DialecticPlayground\Hello\Greeting;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class GreetingTest extends TestCase
{
    public function testDefaultGreeting(): void
    {
        self::assertSame("Hello, World!\n", Greeting::build('World', 1));
    }

    public function testNameIsPreservedExactly(): void
    {
        self::assertSame("Hello, Ada Lovelace!\n", Greeting::build('Ada Lovelace', 1));
        self::assertSame("Hello, Zo\u{eb}!\n", Greeting::build("Zo\u{eb}", 1));
        self::assertSame("Hello, -x!\n", Greeting::build('-x', 1));
    }

    public function testRepetition(): void
    {
        self::assertSame("Hello, Grace!\nHello, Grace!\nHello, Grace!\n", Greeting::build('Grace', 3));
        self::assertSame(1000, substr_count(Greeting::build('A', 1000), "\n"));
    }

    public function testOutputIsLfOnly(): void
    {
        self::assertStringNotContainsString("\r", Greeting::build('World', 5));
    }
}
