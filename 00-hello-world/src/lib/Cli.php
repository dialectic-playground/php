<?php

declare(strict_types=1);

namespace DialecticPlayground\Hello;

/**
 * The pure core of dp-hello: arguments in, output bytes and exit status out (SRS 3, 4, 6).
 */
final class Cli
{
    public const int EXIT_SUCCESS = 0;
    public const int EXIT_USAGE = 2;

    public const string VERSION_TEXT = "dp-hello (dialectic-playground) 1.0.0\n";

    public const string HELP_TEXT = <<<'TEXT'
        Usage: dp-hello [OPTION]...
        Print a greeting to standard output.

        Options:
          -n, --name=NAME     greet NAME instead of World
          -c, --count=COUNT   print the greeting COUNT times (1-1000, default 1)
              --help          display this help and exit
              --version       output version information and exit

        TEXT;

    /**
     * @param list<string> $args the command-line arguments, without the program name
     */
    public static function run(array $args): Outcome
    {
        try {
            $parsed = ArgumentParser::parse($args);
            $stdout = match ($parsed->action) {
                Action::Help => self::HELP_TEXT,
                Action::Version => self::VERSION_TEXT,
                Action::Greet => self::greet(Validator::validate($parsed)),
            };
        } catch (UsageError $error) {
            return new Outcome('', self::diagnostic($error->getMessage()), self::EXIT_USAGE);
        }

        return new Outcome($stdout, '', self::EXIT_SUCCESS);
    }

    /**
     * The two-line usage diagnostic (CONV-13, CONV-14).
     */
    public static function diagnostic(string $message): string
    {
        return "dp-hello: {$message}\nTry 'dp-hello --help' for more information.\n";
    }

    private static function greet(Config $config): string
    {
        return Greeting::build($config->name, $config->count);
    }
}
