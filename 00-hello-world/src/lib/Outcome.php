<?php

declare(strict_types=1);

namespace DialecticPlayground\Hello;

/**
 * Everything one invocation produces: the exact standard output and standard error bytes,
 * and the exit status.
 */
final readonly class Outcome
{
    public function __construct(
        public string $stdout,
        public string $stderr,
        public int $status,
    ) {}
}
