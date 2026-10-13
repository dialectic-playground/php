<?php

declare(strict_types=1);

namespace DialecticPlayground\Hello;

/**
 * An option value as recorded during the scan, with the option as the user spelled it
 * ("-n" or "--name"), which the CONV-13 "invalid argument" diagnostic needs.
 */
final readonly class OptionValue
{
    public function __construct(
        public string $value,
        public string $spelling,
    ) {}
}
