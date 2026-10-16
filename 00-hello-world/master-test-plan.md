<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->

**Table of Contents** _generated with [DocToc](https://github.com/thlorenz/doctoc)_

- [Master Test Plan (MTP) — Standard Terminal Hello World](#master-test-plan-mtp--standard-terminal-hello-world)
  - [1. Introduction and scope](#1-introduction-and-scope)
    - [1.1 Objectives](#11-objectives)
    - [1.2 In scope / out of scope](#12-in-scope--out-of-scope)
  - [2. Test items](#2-test-items)
  - [3. Test levels and approach](#3-test-levels-and-approach)
  - [4. Test design](#4-test-design)
    - [4.1 Equivalence partitions and boundaries](#41-equivalence-partitions-and-boundaries)
    - [4.2 Additional test cases (beyond the supplied suite)](#42-additional-test-cases-beyond-the-supplied-suite)
    - [4.3 Negative and robustness testing](#43-negative-and-robustness-testing)
    - [4.4 Performance and resource testing](#44-performance-and-resource-testing)
  - [5. Test environments](#5-test-environments)
  - [6. Entry and exit criteria](#6-entry-and-exit-criteria)
  - [7. Defect management](#7-defect-management)
  - [8. Schedule and deliverables](#8-schedule-and-deliverables)
  - [9. Risks and contingencies](#9-risks-and-contingencies)
  - [10. Test summary (complete at the end)](#10-test-summary-complete-at-the-end)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

# Master Test Plan (MTP) — Standard Terminal Hello World

| Field                   | Value                                                  |
| :---------------------- | :----------------------------------------------------- |
| Project                 | `00-hello-world`                                       |
| Tools                   | dp-hello                                               |
| Implementation language | php (PHP 8.5, Composer)                                |
| Author                  | Brandon Perkins                                        |
| Version / date          | 1.0 — 2026-10-13                                       |
| Standard                | Structure adapted from IEEE 829 / ISO/IEC/IEEE 29119-3 |

## 1. Introduction and scope

### 1.1 Objectives

"Tested enough" means every SRS acceptance criterion (FR-01 … FR-11) is exercised by a unit
test or a public black-box test, the public and hidden suites pass on Linux, macOS, and
Windows, `make lint` (php -l, PHPStan level max, PHP-CS-Fixer) is clean, and NFR-02 is measured.

### 1.2 In scope / out of scope

| In scope                                                                            | Out of scope                                                    |
| :---------------------------------------------------------------------------------- | :-------------------------------------------------------------- |
| Every FR/NFR in the SRS and the applicable CONV rules, on Linux, macOS, and Windows | Localization, terminals' rendering of UTF-8, PHP engine defects |

## 2. Test items

| Item                   | Version / location                                                                                      |
| :--------------------- | :------------------------------------------------------------------------------------------------------ |
| dp-hello               | `00-hello-world/src/` (launcher `src/bin/dp-hello`, library `src/lib/`) on branch `feat/00-hello-world` |
| Public black-box suite | `tests/public/` (10 tests)                                                                              |
| Hidden black-box suite | `tests/private/` (10 tests, run by CI)                                                                  |

## 3. Test levels and approach

| Level              | Purpose                                                                            | Technique(s)                                              | Tooling                                                        | Owner          |
| :----------------- | :--------------------------------------------------------------------------------- | :-------------------------------------------------------- | :------------------------------------------------------------- | :------------- |
| Unit               | Parser, validator, greeting, output in isolation                                   | Equivalence partitioning, boundary values, data providers | PHPUnit 13 (`make test-unit`)                                  | Developer      |
| Integration        | Parser → validator → greeting via `Cli::run`; the real launcher in a child process | Specification-based                                       | PHPUnit (`CliTest`, `LauncherTest`)                            | Developer      |
| System (black-box) | The specified CLI behavior, byte-exact                                             | Specification-based                                       | `make test` → `run-tests.py`                                   | Developer      |
| Acceptance         | Hidden suite on all OSes                                                           | —                                                         | CI `make check` (`test-hidden`)                                | CI             |
| Static analysis    | Syntax, types, style                                                               | —                                                         | `make lint`: `php -l`, PHPStan level max, PHP-CS-Fixer `check` | Developer / CI |

## 4. Test design

### 4.1 Equivalence partitions and boundaries

| Input         | Valid partitions                                                           | Invalid partitions                                                         | Boundaries                             |
| :------------ | :------------------------------------------------------------------------- | :------------------------------------------------------------------------- | :------------------------------------- |
| NAME          | any non-empty text (spaces, `-x`, `=`, non-ASCII)                          | empty                                                                      | `''`, one character                    |
| COUNT         | digits with value 1–1000, leading zeros                                    | empty, non-digits, sign, space, newline, `.`, full-width digits, 0, > 1000 | 0, 1, 1000, 1001, `0001000`, 26 digits |
| Option tokens | `-n`, `-nV`, `--name V`, `--name=V`, same for count, `--help`, `--version` | unknown short/long, abbreviated long, missing value, `--help=…`            | value at end of argv                   |
| Operands      | none                                                                       | any word, `-`, anything after `--`                                         | `--` as last token                     |
| Scan order    | help/version first reached wins                                            | syntax error before help                                                   | operand or bad value before help       |

### 4.2 Additional test cases (beyond the supplied suite)

| ID   | Requirement(s) | Preconditions | Steps / command                                       | Expected result                                 | Level                        |
| :--- | :------------- | :------------ | :---------------------------------------------------- | :---------------------------------------------- | :--------------------------- |
| T-01 | FR-02, CONV-05 | —             | `dp-hello -c2 --name Zoë` (child process)             | two `Hello, Zoë!` lines, UTF-8, LF only         | integration (`LauncherTest`) |
| T-02 | FR-02          | —             | `--name=-x`, `-n-x`, `--name -x`                      | name `-x`                                       | unit                         |
| T-03 | FR-03          | —             | `--name ''`                                           | `invalid argument '' for '--name'`, exit 2      | unit                         |
| T-04 | FR-04          | —             | `-c 1`, `-c 1000`, `-c 007`                           | 1, 1000, 7 lines                                | unit                         |
| T-05 | FR-05          | —             | `-c 0`, `-c 1001`, `-c abc`, `-c +5`, `-c ' 5'`       | invalid argument, value verbatim                | unit                         |
| T-06 | FR-06          | —             | `-n A -n B`; `-c 0 -c 1`                              | `Hello, B!`; one line                           | unit                         |
| T-07 | FR-07, CONV-12 | —             | `extra --help`, `--count 0 --help`, `--help extra -x` | help, exit 0                                    | unit                         |
| T-08 | CONV-12        | —             | `-x --help`                                           | `invalid option -- 'x'`                         | unit                         |
| T-09 | FR-09, CONV-09 | —             | `-- --help`, `-- -n`                                  | `extra operand '--help'` / `'-n'`               | unit                         |
| T-10 | FR-10, CONV-08 | —             | `--nam=Ada`, `--he`, `---name`                        | `unrecognized option` with the full token       | unit                         |
| T-11 | FR-10          | —             | `-ë`                                                  | `invalid option -- 'ë'` (whole UTF-8 character) | unit                         |
| T-12 | CONV-25        | —             | write to an unwritable stream                         | `Output::write` returns false, no notice        | unit                         |
| T-13 | FR-05, NFR-03  | —             | `-c` with 26 digits                                   | invalid argument, no overflow                   | unit                         |

### 4.3 Negative and robustness testing

Every CONV-13 message has a `ArgumentParserTest::testSyntaxErrors` or `ValidatorTest` data set
and an end-to-end `CliTest::testUsageErrorsWriteOnlyTheDiagnostic` case that also asserts an
empty stdout. PHPUnit fails on any warning, notice, or deprecation (`phpunit.xml.dist`). Missing
files, stdin, and binary input do not apply: the tool reads none.

### 4.4 Performance and resource testing

Measured in the toolchain container (PHP 8.5.11, x86-64), best of 20 runs, peak RSS from
`wait4`: `php -r ''` (runtime baseline, CONV-31) 14.8 ms and 23.6 MiB; `dp-hello -c 1000 -n Zoë`
14.6 ms and 24.5 MiB, i.e. about 1 MiB above the baseline. Both are far within NFR-02 (300 ms
for an interpreted runtime, 32 MiB above the baseline).

## 5. Test environments

| Environment | OS / version                                | Toolchain                                                                | How provisioned            |
| :---------- | :------------------------------------------ | :----------------------------------------------------------------------- | :------------------------- |
| Local       | Fedora 44 + Podman                          | PHP 8.5 in `php:8.5-cli-trixie` (Containerfile), Composer 2              | `make check`               |
| CI          | ubuntu-latest, macos-latest, windows-latest | PHP 8.5 + Composer via shivammathur/setup-php; container job with Podman | `.github/workflows/ci.yml` |

## 6. Entry and exit criteria

- **Entry:** HLD/LLD reviewed, `make all` succeeds, `make lint` is clean.
- **Exit:** all unit tests pass; public and hidden black-box tests pass on all three OSes and in
  the container; `make roundtrip` passes natively; the RTM shows every requirement Verified.
- **Suspension / resumption:** suspend on a red CI job; resume after the fix and a full re-run.

## 7. Defect management

Defects are GitHub issues in this repository, labelled by severity. Each fix adds a unit test
(or a data set) that fails before the fix, and CI re-runs the full matrix.

| Severity     | Definition                                                         |
| :----------- | :----------------------------------------------------------------- |
| 1 — Critical | Crash, stack trace, or wrong output/exit status on a mainline path |
| 2 — Major    | Wrong diagnostic text or platform-specific byte difference         |
| 3 — Minor    | Lint, documentation, or tooling issue with no behavior change      |

## 8. Schedule and deliverables

| Milestone               | Deliverable                | Target date |
| :---------------------- | :------------------------- | :---------- |
| Design complete         | hld.md, lld.md             | 2026-10-13  |
| Implementation complete | src/, unit tests           | 2026-10-13  |
| Verification complete   | RTM all Verified, CI green | 2026-10-13  |

## 9. Risks and contingencies

| Risk                                                     | Mitigation                                                                      |
| :------------------------------------------------------- | :------------------------------------------------------------------------------ |
| Windows-only failure (argv encoding, `#!` launcher)      | `LauncherTest` and the hidden suite run on windows-latest from the first commit |
| Dev-tool versions differ between container and native CI | Exact pins in `composer.json` + `composer.lock`                                 |

## 10. Test summary (complete at the end)

| Metric                                | Value                                                                              |
| :------------------------------------ | :--------------------------------------------------------------------------------- |
| Unit tests (pass / total)             | 90 / 90 (146 assertions)                                                           |
| Coverage (line / branch)              | Not measured (no coverage driver in the image); every public function is exercised |
| Public black-box (pass / 10)          | 10 / 10                                                                            |
| Hidden black-box (pass / 10, from CI) | 10 / 10                                                                            |
| Open defects by severity              | 0 / 0 / 0                                                                          |
