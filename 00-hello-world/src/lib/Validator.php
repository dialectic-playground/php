<?php

declare(strict_types=1);

namespace DialecticPlayground\Hello;

/**
 * Post-scan checks (CONV-12): operands first (FR-09), then the winning --name (FR-03) and
 * --count (FR-04, FR-05) values.
 */
final class Validator
{
    public const string DEFAULT_NAME = 'World';
    public const int DEFAULT_COUNT = 1;
    public const int MIN_COUNT = 1;
    public const int MAX_COUNT = 1000;

    /**
     * @throws UsageError
     */
    public static function validate(ParsedArguments $parsed): Config
    {
        if (null !== $parsed->firstOperand) {
            throw new UsageError(\sprintf("extra operand '%s'", $parsed->firstOperand));
        }

        return new Config(
            null === $parsed->name ? self::DEFAULT_NAME : self::name($parsed->name),
            null === $parsed->count ? self::DEFAULT_COUNT : self::count($parsed->count),
        );
    }

    /**
     * Any non-empty string is a valid name, used verbatim (FR-02, FR-03).
     *
     * @throws UsageError
     */
    public static function name(OptionValue $option): string
    {
        if ('' === $option->value) {
            throw UsageError::invalidArgument($option);
        }

        return $option->value;
    }

    /**
     * ASCII digits only, leading zeros allowed, value 1-1000 (FR-04, FR-05).
     *
     * @return int<1, 1000>
     *
     * @throws UsageError
     */
    public static function count(OptionValue $option): int
    {
        if (1 !== preg_match('/\A[0-9]+\z/', $option->value)) {
            throw UsageError::invalidArgument($option);
        }
        // Strip leading zeros so that an arbitrarily long digit string never overflows an int.
        $significant = ltrim($option->value, '0');
        $count = \strlen($significant) > \strlen((string) self::MAX_COUNT) ? 0 : (int) $significant;
        if ($count < self::MIN_COUNT || $count > self::MAX_COUNT) {
            throw UsageError::invalidArgument($option);
        }

        return $count;
    }
}
