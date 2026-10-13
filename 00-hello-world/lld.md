# Low-Level Design (LLD) — Standard Terminal Hello World

| Field | Value |
| :-- | :-- |
| Project | `00-hello-world` |
| Tools | dp-hello |
| Implementation language | php (PHP 8.5, Composer) |
| Author | Brandon Perkins |
| Version / date | 1.0 — 2026-10-13 |
| Status | In review |
| Inputs | `hld.md`, `specs/SRS.md`, `shared/CLI-CONVENTIONS.md` |

## 1. Source layout

```text
00-hello-world/
├── composer.json, composer.lock   # PSR-4 autoload + exact dev tools (PHPUnit, PHPStan, PHP-CS-Fixer)
├── phpunit.xml.dist, phpstan.neon.dist (level max), .php-cs-fixer.dist.php
└── src/
    ├── bin/dp-hello               # #!/usr/bin/env php launcher (BIN_DIR)
    ├── lib/                       # namespace DialecticPlayground\Hello
    │   ├── Action.php             # enum Greet | Help | Version
    │   ├── OptionValue.php        # recorded value + option spelling
    │   ├── ParsedArguments.php    # scan result
    │   ├── UsageError.php         # usage error (CONV-13 message)
    │   ├── ArgumentParser.php     # the scan (CONV-07 … CONV-12)
    │   ├── Config.php             # validated request
    │   ├── Validator.php          # post-scan checks (FR-03, FR-05, FR-09)
    │   ├── Greeting.php           # pure greeting construction
    │   ├── Outcome.php            # stdout, stderr, status
    │   ├── Cli.php                # pure core, help/version text, diagnostics
    │   └── Output.php             # byte writer
    └── tests/                     # PHPUnit: ArgumentParserTest, ValidatorTest, GreetingTest,
                                   # CliTest, OutputTest, LauncherTest
```

## 2. Data types

### 2.1 `ParsedArguments` (readonly)
| Field | Type | Default | Constraint / invariant | SRS |
| :-- | :-- | :-- | :-- | :-- |
| action | `Action` | `Greet` | `Help`/`Version` when the scan stopped there | FR-07, FR-08 |
| name | `?OptionValue` | null | last `-n`/`--name` (unvalidated) | FR-02, FR-06 |
| count | `?OptionValue` | null | last `-c`/`--count` (unvalidated) | FR-04, FR-06 |
| firstOperand | `?string` | null | first operand, including after `--` | FR-09 |

`OptionValue(value: string, spelling: string)`: `spelling` is `-n`, `--name`, `-c`, or `--count`.

### 2.2 `Config` (readonly)
| Field | Type | Default | Constraint / invariant | SRS |
| :-- | :-- | :-- | :-- | :-- |
| name | `string` | `World` | non-empty | FR-01 … FR-03 |
| count | `int<1, 1000>` | 1 | 1 ≤ count ≤ 1000 (PHPStan-checked) | FR-04 |

### 2.3 Error type
| Variant | Diagnostic (stderr) | Exit status |
| :-- | :-- | :-- |
| `UsageError(msg)` | `dp-hello: <msg>` LF `Try 'dp-hello --help' for more information.` LF | 2 |

Messages: `invalid option -- 'x'`, `unrecognized option 'T'`, `option requires an argument --
'n'`, `option '--name' requires an argument`, `option '--help' doesn't allow an argument`,
`invalid argument 'V' for '<spelling>'`, `extra operand 'X'` (SRS §6).

## 3. Module and function specifications

### 3.1 `ArgumentParser::parse`
- **Signature:** `public static function parse(list<string> $args): ParsedArguments` (throws `UsageError`)
- **Responsibility:** the CONV-12 scan.
- **Algorithm:** for each token, left to right:
  1. After `--`, or if the token is `-` or does not start with `-`: record it as the first
     operand (if none yet); continue.
  2. `--`: switch to operands-only; continue.
  3. `--BODY`: split BODY at the first `=` (`splitLong`). `help`/`version`: with `=` → error
     "doesn't allow an argument"; else return `ParsedArguments(Help|Version)` at once.
     `name`/`count`: value is the attached text (may be empty) or the next token (any text,
     even `-x`); none → "requires an argument". Anything else → "unrecognized option" with the
     full token.
  4. `-X…`: X is the first UTF-8 character (`firstCharacter`). Not `n`/`c` → "invalid
     option". Otherwise the value is the rest of the token, or the next token if the rest is
     empty; none → "option requires an argument".
  5. Recording a value replaces the earlier one (last wins).
- **Complexity:** O(total argument length) time, O(1) extra memory.
- **Satisfies:** FR-02, FR-06, FR-07, FR-08, FR-10, CONV-07 … CONV-12.
- **Unit tests:** `ArgumentParserTest::*`.

### 3.2 `Validator::validate`, `Validator::name`, `Validator::count`
- **Signatures:** `validate(ParsedArguments): Config`, `name(OptionValue): string`,
  `count(OptionValue): int<1, 1000>` (all throw `UsageError`).
- **Algorithm:** operand present → `extra operand`; then the name (empty → invalid argument);
  then the count: must match `/\A[0-9]+\z/`; strip leading zeros; more than 4 significant
  digits or a value outside 1–1000 → invalid argument (with the value exactly as given).
- **Satisfies:** FR-03, FR-04, FR-05, FR-09, CONV-15. **Unit tests:** `ValidatorTest::*`.

### 3.3 `Greeting::build`
- **Signature:** `public static function build(string $name, int<0, max> $count): string`
- **Responsibility:** `str_repeat("Hello, {$name}!\n", $count)`; pure, no I/O.
- **Satisfies:** FR-01, FR-02, FR-04, NFR-05. **Unit tests:** `GreetingTest::*`.

### 3.4 `Cli::run`, `Cli::diagnostic`
- **Signature:** `run(list<string> $args): Outcome`; `diagnostic(string $message): string`
- **Algorithm:** parse; Help → `HELP_TEXT`, Version → `VERSION_TEXT`, Greet → validate and
  build; `catch (UsageError)` → `Outcome('', diagnostic(message), 2)`; else `Outcome(text, '', 0)`.
- **Satisfies:** FR-01 … FR-11, CONV-02, CONV-13, CONV-14, CONV-23. **Unit tests:** `CliTest::*`.

### 3.5 `Output::emit`, `Output::write`
- **Signatures:** `emit(Outcome, resource $stdout, resource $stderr): int`;
  `write(resource, string): bool`
- **Algorithm:** loop `fwrite` until all bytes are written; `false` or 0 bytes (EPIPE) → return
  false quietly. A temporary error handler swallows PHP's write notice (CONV-25).
- **Satisfies:** CONV-03, CONV-25. **Unit tests:** `OutputTest::*`.

### 3.6 Launcher `src/bin/dp-hello`
Registers a PSR-4 autoloader for `src/lib`, sets `display_errors=stderr`, takes the string
elements of `$_SERVER['argv']` after the program name, and
`exit(Output::emit(Cli::run($arguments), STDOUT, STDERR))`. **Tests:** `LauncherTest::*`, P01–P10.

## 4. Algorithms

The only algorithms are the scan (§3.1) and the count check (§3.2). Stripping leading zeros
before converting means a 10 000-digit count is rejected without integer overflow; the regex
uses `\z`, so a trailing newline is not accepted.

## 5. Platform-specific details

| Concern | Linux | macOS | Windows | Abstraction |
| :-- | :-- | :-- | :-- | :-- |
| Newlines on stdout/stderr | untranslated | untranslated | untranslated (PHP streams are binary) | `Output` |
| argv encoding | UTF-8 bytes | UTF-8 bytes | php-cli converts the UTF-16 command line to UTF-8 (`default_charset`) | launcher |
| Starting the tool | `#!` | `#!` | runner / Git Bash interprets `#!` | launcher |
| Broken pipe | SIGPIPE ignored by php-cli; `fwrite` fails | same | `fwrite` fails | `Output::write` |

## 6. Resource management

No files, sockets, or child processes. The whole output (≤ 1000 × (9 + |NAME|) bytes) is
built in one string and written once; the standard streams are owned by php-cli.

## 7. Sequence of one invocation

`dp-hello -c2 -n Ken` (P06): php-cli runs the launcher → `$arguments = ['-c2', '-n', 'Ken']`
→ `ArgumentParser::parse`: `-c2` records `OptionValue('2', '-c')`; `-n` takes `Ken` →
`ParsedArguments(Greet, name, count, null)` → `Validator::validate` → `Config('Ken', 2)` →
`Greeting::build('Ken', 2)` = `"Hello, Ken!\nHello, Ken!\n"` → `Outcome(text, '', 0)` →
`Output::emit` writes stdout → exit 0.

## 8. Design-to-code checklist

| HLD component | LLD section | Source file(s) | Unit-test file(s) |
| :-- | :-- | :-- | :-- |
| ArgumentParser | §3.1 | `src/lib/ArgumentParser.php`, `ParsedArguments.php`, `OptionValue.php`, `Action.php` | `src/tests/ArgumentParserTest.php` |
| Validator | §3.2 | `src/lib/Validator.php`, `Config.php`, `UsageError.php` | `src/tests/ValidatorTest.php` |
| Greeting | §3.3 | `src/lib/Greeting.php` | `src/tests/GreetingTest.php` |
| Cli | §3.4 | `src/lib/Cli.php`, `Outcome.php` | `src/tests/CliTest.php` |
| Output | §3.5 | `src/lib/Output.php` | `src/tests/OutputTest.php` |
| Launcher | §3.6 | `src/bin/dp-hello` | `src/tests/LauncherTest.php` |
