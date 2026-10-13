<?php

declare(strict_types=1);

namespace DialecticPlayground\Hello;

/**
 * The I/O shell: writes an Outcome's bytes unchanged. PHP streams never translate LF, on
 * Windows included, so the output is LF-only everywhere (CONV-03).
 */
final class Output
{
    /**
     * Writes the outcome to the given streams and returns the exit status.
     *
     * @param resource $stdout
     * @param resource $stderr
     */
    public static function emit(Outcome $outcome, $stdout, $stderr): int
    {
        self::write($stdout, $outcome->stdout);
        self::write($stderr, $outcome->stderr);

        return $outcome->status;
    }

    /**
     * Writes all of $bytes, or stops quietly when the reader has gone away (CONV-25).
     *
     * @param resource $stream
     */
    public static function write($stream, string $bytes): bool
    {
        // A broken pipe makes fwrite() raise a notice; it must not reach the user.
        set_error_handler(static fn (): bool => true);

        try {
            while ('' !== $bytes) {
                $written = fwrite($stream, $bytes);
                if (false === $written || 0 === $written) {
                    return false;
                }
                $bytes = substr($bytes, $written);
            }

            return fflush($stream);
        } finally {
            restore_error_handler();
        }
    }
}
