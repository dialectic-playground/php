<?php

declare(strict_types=1);

namespace DialecticPlayground\Hello;

/**
 * A usage error (CONV-13): the message is the diagnostic text after "dp-hello: ".
 */
final class UsageError extends \Exception
{
    public static function invalidArgument(OptionValue $option): self
    {
        return new self(\sprintf("invalid argument '%s' for '%s'", $option->value, $option->spelling));
    }
}
