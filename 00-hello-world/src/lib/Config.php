<?php

declare(strict_types=1);

namespace DialecticPlayground\Hello;

/**
 * A validated greeting request. Invariants: $name is non-empty, 1 <= $count <= 1000.
 */
final readonly class Config
{
    /**
     * @param int<1, 1000> $count
     */
    public function __construct(
        public string $name = Validator::DEFAULT_NAME,
        public int $count = Validator::DEFAULT_COUNT,
    ) {}
}
