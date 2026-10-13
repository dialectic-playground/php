<?php

declare(strict_types=1);

namespace DialecticPlayground\Hello;

/**
 * Greeting construction (NFR-05): a pure function with no I/O.
 */
final class Greeting
{
    /**
     * "Hello, NAME!" followed by LF, repeated $count times (FR-01, FR-02, FR-04).
     *
     * @param int<0, max> $count
     */
    public static function build(string $name, int $count): string
    {
        return str_repeat('Hello, '.$name."!\n", $count);
    }
}
