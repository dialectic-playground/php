# High-Level Design (HLD) — Standard Terminal Hello World

| Field | Value |
| :-- | :-- |
| Project | `00-hello-world` |
| Tools | dp-hello |
| Implementation language | php (PHP 8.5, Composer) |
| Author | Brandon Perkins |
| Version / date | 1.0 — 2026-10-13 |
| Status | In review |
| Inputs | `specs/URD.md`, `specs/SRS.md`, `specs/user-manual.md`, `shared/CLI-CONVENTIONS.md` |

## 1. Introduction

### 1.1 Scope
`dp-hello` writes `Hello, ` + NAME + `!` + LF to standard output COUNT times (NAME defaults to
`World`, COUNT to 1), prints the help or version text, and reports usage errors with the exact
CONV-13/14 diagnostics and exit status 2. It reads no input, takes no operands, touches no
files, reads no environment variables, and runs no subprocesses (SRS §1.2, NFR-07).

### 1.2 Definitions and acronyms
| Term | Meaning |
| :-- | :-- |
| Scan | The left-to-right pass over the arguments (CONV-12) |
| Syntax error | Unknown option, missing option argument, or `=value` on a flag; reported during the scan |
| Value / operand error | Invalid `--name`/`--count` value or an operand; reported after the scan |
| Outcome | The stdout bytes, stderr bytes, and exit status of one invocation |
| Launcher | `src/bin/dp-hello`, the extensionless `#!/usr/bin/env php` script |

### 1.3 References
- SRS §3 (interfaces), §4 (FR-01 … FR-11), §5 (NFR-01 … NFR-07), §6 (diagnostics)
- CLI-CONV CONV-03, CONV-05, CONV-07 … CONV-15, CONV-24, CONV-25, CONV-31
- User manual, "HELP OUTPUT"

## 2. Requirements summary

| Area | SRS requirements | Architectural impact |
| :-- | :-- | :-- |
| Option parsing | FR-02, FR-06, FR-07, FR-08, FR-10, CONV-07 … CONV-12 | Hand-written `ArgumentParser` that only records values and operands |
| Validation | FR-03, FR-04, FR-05, FR-09 | `Validator` runs after the scan and produces a `Config` |
| Greeting | FR-01, FR-02, FR-04, NFR-05 | Pure `Greeting::build` |
| Diagnostics, exit status | FR-11, SRS §3.7, §6, CONV-13/14 | One `UsageError` type, mapped to text and status 2 in `Cli::run` only |
| Output bytes | NFR-01, CONV-03, CONV-05, CONV-25 | `Output` writes bytes unchanged; tolerates a broken pipe |
| Build / install | NFR-06, UR-12 | Script tool: launcher in `src/bin`, payload installed under `LIBDIR` |

## 3. Architectural overview

### 3.1 Context diagram

```text
                  ┌──────────────────────────────┐
   argv (UTF-8) ─▶│ dp-hello (php-cli + src/lib) │──▶ stdout (greeting / help / version)
                  │                              │──▶ stderr (usage diagnostic)
                  └──────────────────────────────┘──▶ exit status 0 or 2
   (stdin, files, environment, network: not used)
```

### 3.2 Component (module) decomposition

| Component | Responsibility | Depends on |
| :-- | :-- | :-- |
| Launcher (`src/bin/dp-hello`) | Load the library, collect `argv`, call `Cli::run`, emit, exit | Cli, Output |
| `ArgumentParser` | Scan argv into `ParsedArguments` or throw a syntax `UsageError` | OptionValue, ParsedArguments, Action, UsageError |
| `Validator` | Check operands and the winning values; produce `Config` | Config, UsageError |
| `Greeting` | Build the greeting text (pure) | — |
| `Cli` | Pure core: arguments → `Outcome`; owns help/version text and the error → diagnostic mapping | all of the above |
| `Output` | Write an `Outcome`'s bytes to stdout/stderr; return the status | Outcome |

### 3.3 Data flow
argv → `ArgumentParser::parse` → `ParsedArguments` (action Help/Version → fixed text) →
`Validator::validate` → `Config` → `Greeting::build` → `Outcome(stdout, '', 0)`. Any
`UsageError` becomes `Outcome('', diagnostic, 2)`. The launcher passes the `Outcome` to
`Output::emit` and exits with its status. Nothing is written until the whole outcome is known,
so usage errors never produce partial stdout (FR-11).

## 4. Key design decisions

| # | Decision | Alternatives considered | Rationale |
| :-- | :-- | :-- | :-- |
| D1 | Pure `Cli::run(args): Outcome` with a thin I/O shell | Writing from inside the parser / `exit()` calls | End-to-end unit tests without processes; single error mapping |
| D2 | Two phases: scan (syntax) then validate (values, operands) | Validate values while scanning | CONV-12 (v1.4.0): value and operand errors never beat `--help`/`--version` |
| D3 | Errors as one exception type `UsageError` carrying the message | Error codes / result objects | Idiomatic PHP; one `catch` in `Cli::run` |
| D4 | Buffer the whole output (≤ 1000 short lines) and write once | Stream line by line | Atomicity (FR-11) and simplicity; bounded size |
| D5 | Launcher loads `src/lib` with its own autoloader | Composer's `vendor/autoload.php` at run time | No run-time dependency on `composer install`; the payload is just `bin/` + `lib/` |
| D6 | Composer installs only the dev tools, pinned exactly with `composer.lock` | Global tools / PHARs | Same PHPUnit/PHPStan/PHP-CS-Fixer versions in the container and on every OS |

## 5. Cross-platform strategy

- **Line endings (CONV-03):** PHP streams never translate newlines (`STDOUT`/`STDERR` are binary
  on Windows too); every generated line ends in `"\n"`. `Output` writes the bytes unchanged.
- **UTF-8 arguments (CONV-05):** on POSIX `argv` bytes pass through. On Windows, php-cli (7.1+)
  reads the UTF-16 command line and converts it with its current code page, which follows
  `default_charset` (UTF-8 by default), so `$argv` holds UTF-8. `LauncherTest` verifies `Zoë`
  end to end on all three CI operating systems. The code never transcodes or uses `mb_*`/locale
  functions (CONV-06).
- **Paths:** none are used (no files).
- **Script tool on Windows:** the black-box runner starts the `#!` interpreter (`php`) itself;
  users run the tool from Git Bash or MSYS2. The installed launcher is a `#!/bin/sh` script.
- Platform differences are confined to the launcher and `Output`; the core is platform-free.

## 6. Error handling and diagnostics

`ArgumentParser` and `Validator` throw `UsageError` whose message is the CONV-13 text after
`dp-hello: `. `Cli::run` is the only place that catches it and builds the two-line diagnostic
(`Cli::diagnostic`, CONV-14) with exit status 2. There are no runtime errors besides a broken
pipe, which `Output::write` absorbs silently (CONV-25); the status is then still the outcome's.
The launcher sends any PHP diagnostic to stderr (`display_errors=stderr`); no code path throws
an uncaught exception (CONV-24).

## 7. Non-functional design

| Quality attribute | SRS reference | How the design achieves it |
| :-- | :-- | :-- |
| Performance / memory | NFR-02, CONV-31 | O(1) work besides at most 1000 short lines; measured ≈ 15 ms and ≈ 1 MiB above the php-cli baseline (master test plan §4.4) |
| Portability | NFR-01 | Binary, untranslated streams; UTF-8 argv; tested on Linux, macOS, Windows |
| Robustness | NFR-03 | Total parser (every token handled), `UsageError` only, broken pipe absorbed |
| Security / input validation | NFR-07 | No files, environment variables, or subprocesses in `src/lib`; strict count regex |
| Maintainability / testability | NFR-04, NFR-05 | One class per unit; pure `Greeting::build` and `Cli::run`; PHPStan level max |

## 8. Risks and open issues

| Risk / issue | Likelihood | Impact | Mitigation |
| :-- | :-- | :-- | :-- |
| Windows php-cli delivers ANSI-code-page `argv` | L | H | `LauncherTest` + hidden tests on Windows CI |
| Formatter/linter versions drift between environments | L | M | Exact versions in `composer.json` and `composer.lock` |
| PHP start-up warnings from a host `php.ini` reach stdout | L | M | Launcher sets `display_errors=stderr` |

## 9. Approval

| Role | Name | Date | Decision |
| :-- | :-- | :-- | :-- |
| Author | Brandon Perkins | 2026-10-13 | Submitted |
| Reviewer | | | |
