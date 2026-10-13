<?php

declare(strict_types=1);

namespace DialecticPlayground\Hello;

/**
 * Hand-written option parser for dp-hello (SRS 3.2, CONV-07 ... CONV-13).
 *
 * The scan runs left to right. --help and --version end it at once; option syntax errors
 * (unknown option, missing argument, an argument given to a flag) are thrown as soon as they
 * are reached. Operands and option values are only recorded (CONV-12).
 */
final class ArgumentParser
{
    /** Long options that take a required argument, mapped to their short letter. */
    private const array VALUE_OPTIONS = ['name' => 'n', 'count' => 'c'];

    /**
     * @param list<string> $args the command-line arguments, without the program name
     *
     * @throws UsageError on an option syntax error
     */
    public static function parse(array $args): ParsedArguments
    {
        /** @var array<string, OptionValue> $values keyed by short letter */
        $values = [];
        $firstOperand = null;
        $count = \count($args);
        $onlyOperands = false;

        for ($index = 0; $index < $count; ++$index) {
            $token = $args[$index];
            if ($onlyOperands || '-' === $token || !str_starts_with($token, '-')) {
                $firstOperand ??= $token;

                continue;
            }
            if ('--' === $token) {
                $onlyOperands = true;

                continue;
            }

            if (str_starts_with($token, '--')) {
                [$name, $attached] = self::splitLong(substr($token, 2));
                if ('help' === $name || 'version' === $name) {
                    if (null !== $attached) {
                        throw new UsageError(\sprintf("option '--%s' doesn't allow an argument", $name));
                    }

                    return new ParsedArguments('help' === $name ? Action::Help : Action::Version);
                }
                if (!isset(self::VALUE_OPTIONS[$name])) {
                    throw new UsageError(\sprintf("unrecognized option '%s'", $token));
                }
                if (null === $attached) {
                    if ($index + 1 >= $count) {
                        throw new UsageError(\sprintf("option '--%s' requires an argument", $name));
                    }
                    $attached = $args[++$index];
                }
                $values[self::VALUE_OPTIONS[$name]] = new OptionValue($attached, '--'.$name);

                continue;
            }

            // A short option: dp-hello has no flag-type short options, so the first letter
            // either takes the rest of the token (or the next token) as its value, or is invalid.
            $letter = self::firstCharacter(substr($token, 1));
            if (!\in_array($letter, self::VALUE_OPTIONS, true)) {
                throw new UsageError(\sprintf("invalid option -- '%s'", $letter));
            }
            $value = substr($token, 2);
            if ('' === $value) {
                if ($index + 1 >= $count) {
                    throw new UsageError(\sprintf("option requires an argument -- '%s'", $letter));
                }
                $value = $args[++$index];
            }
            $values[$letter] = new OptionValue($value, '-'.$letter);
        }

        return new ParsedArguments(Action::Greet, $values['n'] ?? null, $values['c'] ?? null, $firstOperand);
    }

    /**
     * Splits "name=value" into ["name", "value"], and "name" into ["name", null].
     *
     * @return array{string, ?string}
     */
    private static function splitLong(string $body): array
    {
        $equals = strpos($body, '=');

        return false === $equals
            ? [$body, null]
            : [substr($body, 0, $equals), substr($body, $equals + 1)];
    }

    /**
     * The first UTF-8 character of a non-empty string, so that an unknown non-ASCII option
     * letter is reported whole (CONV-05); the first byte if the string is not valid UTF-8.
     */
    private static function firstCharacter(string $text): string
    {
        return 1 === preg_match('/\A./su', $text, $match) ? $match[0] : $text[0];
    }
}
