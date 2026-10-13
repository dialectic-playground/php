# Requirements Traceability Matrix (RTM) — Standard Terminal Hello World

| Field | Value |
| :-- | :-- |
| Project | `00-hello-world` |
| Tools | dp-hello |
| Author | Brandon Perkins |
| Version / date | 1.0 — 2026-10-13 |

Code paths are relative to `src/lib/` (the launcher is `src/bin/dp-hello`); unit tests are in
`src/tests/` (`Class::method`, data-set names in brackets). Public black-box tests are `P01` …
`P10` (`tests/public` of the curriculum). The hidden suite runs in CI on Linux, macOS, and
Windows and is not cited.

## 1. User requirements to system requirements

| User requirement (URD) | Derived system requirements (SRS) |
| :-- | :-- |
| UR-01 | FR-01 |
| UR-02 | FR-02 |
| UR-03 | FR-04 |
| UR-04 | FR-07 |
| UR-05 | FR-08 |
| UR-06 | FR-03, FR-05, FR-09, FR-10, FR-11 |
| UR-07 | FR-01, SRS §3.7 |
| UR-08 | NFR-01, CONV-03 |
| UR-09 | FR-02, CONV-05 |
| UR-10 | FR-06, FR-10, CONV-07 … CONV-15 |
| UR-11 | NFR-02 |
| UR-12 | NFR-06 |

## 2. Forward traceability

| Req. ID | Summary | Design (HLD/LLD) | Code (file::function) | Verification (unit / black-box) | Status |
| :-- | :-- | :-- | :-- | :-- | :-- |
| UR-01 … UR-12 | User needs | HLD §2 | via the FR/NFR/CONV rows below | via the rows below; P01–P10 | Verified |
| FR-01 | Default greeting | HLD §3.3, LLD §3.3 | `Validator.php::validate`, `Greeting.php::build` | `GreetingTest::testDefaultGreeting`, `ValidatorTest::testDefaults`, `CliTest::testSuccess[default]`, P01 | Verified |
| FR-02 | Named greeting, verbatim, UTF-8 | HLD §5, LLD §3.1, §3.3 | `ArgumentParser.php::parse`, `Greeting.php::build` | `ArgumentParserTest::testNameSpellings`, `GreetingTest::testNameIsPreservedExactly`, `CliTest::testSuccess[UTF-8 name]`, `LauncherTest::testUtf8NameAndLfOnlyOutput`, P02, P03, P04 | Verified |
| FR-03 | Empty name rejected | LLD §3.2 | `Validator.php::name` | `ValidatorTest::testEmptyNameIsRejectedWithTheOptionAsWritten`, `CliTest::testUsageErrorsWriteOnlyTheDiagnostic[empty name]` | Verified |
| FR-04 | Repetition 1–1000, leading zeros | LLD §3.2, §3.3 | `Validator.php::count`, `Greeting.php::build` | `ValidatorTest::testValidCounts`, `GreetingTest::testRepetition`, P05, P06 | Verified |
| FR-05 | Invalid count rejected | LLD §3.2 | `Validator.php::count` | `ValidatorTest::testInvalidCounts`, `CliTest::testUsageErrorsWriteOnlyTheDiagnostic[zero count]` | Verified |
| FR-06 | Last occurrence wins | LLD §3.1 | `ArgumentParser.php::parse` | `ArgumentParserTest::testLastOccurrenceWins`, `CliTest::testSuccess[last wins]`, `[invalid value overridden]` | Verified |
| FR-07 | Help, scan order | HLD §4 D2, LLD §3.1, §3.4 | `ArgumentParser.php::parse`, `Cli.php::run` (`HELP_TEXT`) | `ArgumentParserTest::testHelpAndVersionEndTheScan`, `CliTest::testSuccess[help*]`, P07 | Verified |
| FR-08 | Version | LLD §3.4 | `Cli.php::run` (`VERSION_TEXT`) | `CliTest::testSuccess[version]`, P08 | Verified |
| FR-09 | Operands rejected | LLD §3.1, §3.2 | `ArgumentParser.php::parse`, `Validator.php::validate` | `ArgumentParserTest::testOperandsAreRecordedNotRejected`, `testDoubleDashTurnsEverythingAfterItIntoOperands`, `ValidatorTest::testOperandIsReportedBeforeInvalidValues`, P10 | Verified |
| FR-10 | Unknown / malformed options | LLD §3.1 | `ArgumentParser.php::parse` | `ArgumentParserTest::testSyntaxErrors`, `CliTest::testUsageErrorsWriteOnlyTheDiagnostic`, P09 | Verified |
| FR-11 | No stdout on error | HLD §3.3 | `Cli.php::run` | `CliTest::testUsageErrorsWriteOnlyTheDiagnostic`, `LauncherTest::testUsageErrorGoesToStandardError`, P09, P10 | Verified |
| NFR-01 | Byte-identical, LF only | HLD §5, LLD §5 | `Output.php::write`, `Greeting.php::build` | `GreetingTest::testOutputIsLfOnly`, `LauncherTest::testUtf8NameAndLfOnlyOutput`, P01–P10 on 3 OSes (CI) | Verified |
| NFR-02 | ≤ 300 ms, ≤ 32 MiB above baseline | HLD §7 | whole program | Measurement, master test plan §4.4 | Verified |
| NFR-03 | No crash / stack trace | HLD §6 | `ArgumentParser.php::parse`, `Output.php::write`, `src/bin/dp-hello` | `ArgumentParserTest::testSyntaxErrors`, `ValidatorTest::testInvalidCounts[huge]`, `OutputTest::testWriteToAnUnwritableStreamFailsQuietly` | Verified |
| NFR-04 | Separate units | HLD §3.2 | one class per unit (`ArgumentParser`, `Validator`, `Greeting`, `Output`) | Design review; PHPStan level max | Verified |
| NFR-05 | Pure greeting function | LLD §3.3 | `Greeting.php::build` | `GreetingTest::*` | Verified |
| NFR-06 | All Makefile targets | HLD §2 | `00-hello-world/Makefile` | CI `make check roundtrip` (container + 3 OSes) | Verified |
| NFR-07 | No files, environment, subprocesses | HLD §7 | `src/lib/*` | Inspection | Verified |
| CONV-01 | Canonical name | LLD §2.3 | `Cli.php::diagnostic`, `HELP_TEXT`, `VERSION_TEXT` | `CliTest::*`, P07–P10 | Verified |
| CONV-02 | Version line | LLD §3.4 | `Cli.php::VERSION_TEXT` | `CliTest::testSuccess[version]`, P08 | Verified |
| CONV-03 | LF output, binary streams | HLD §5 | `Output.php::write` | `LauncherTest::testUtf8NameAndLfOnlyOutput`, P01–P10 | Verified |
| CONV-05 | UTF-8 arguments | HLD §5, LLD §5 | `src/bin/dp-hello`, `ArgumentParser.php::firstCharacter` | `LauncherTest::testUtf8NameAndLfOnlyOutput`, `ArgumentParserTest::testSyntaxErrors[unknown short non-ASCII]` | Verified |
| CONV-06 | Locale-independent | HLD §5 | no locale or `mb_*` calls | Inspection | Verified |
| CONV-07 | Short options, attached values | LLD §3.1 | `ArgumentParser.php::parse` | `ArgumentParserTest::testBundledShortOptionsTakeTheRestOfTheToken`, P06 | Verified |
| CONV-08 | Long options, no abbreviation | LLD §3.1 | `ArgumentParser.php::parse`, `splitLong` | `ArgumentParserTest::testNameSpellings`, `testSyntaxErrors[abbreviated long]`, P04 | Verified |
| CONV-09 | `--` terminator | LLD §3.1 | `ArgumentParser.php::parse` | `ArgumentParserTest::testDoubleDashTurnsEverythingAfterItIntoOperands` | Verified |
| CONV-10 | Operands, `-` | LLD §3.1 | `ArgumentParser.php::parse` | `ArgumentParserTest::testOperandsAreRecordedNotRejected` | Verified |
| CONV-11 | Repetition | LLD §3.1 | `ArgumentParser.php::parse` | `ArgumentParserTest::testLastOccurrenceWins` | Verified |
| CONV-12 | Scan order | HLD §4 D2 | `ArgumentParser.php::parse`, `Validator.php::validate` | `ArgumentParserTest::testHelpAndVersionEndTheScan`, `CliTest::testSuccess[help after invalid count]`, `testUsageErrorsWriteOnlyTheDiagnostic[invalid option before help]` | Verified |
| CONV-13 | Diagnostic formats | LLD §2.3 | `ArgumentParser.php::parse`, `UsageError.php::invalidArgument` | `ArgumentParserTest::testSyntaxErrors`, `CliTest::testUsageErrorsWriteOnlyTheDiagnostic`, P09, P10 | Verified |
| CONV-14 | Try line, exit 2 | LLD §3.4 | `Cli.php::diagnostic` | `CliTest::testUsageErrorsWriteOnlyTheDiagnostic`, P09, P10 | Verified |
| CONV-15 | Values verbatim, `''` | LLD §3.2 | `UsageError.php::invalidArgument` | `ValidatorTest::testInvalidCounts`, `testEmptyNameIsRejectedWithTheOptionAsWritten` | Verified |
| CONV-18 | Exit statuses | LLD §3.4 | `Cli.php::run` | `CliTest::*`, P01–P10 | Verified |
| CONV-22, CONV-23 | `--help`/`--version`, verbatim help | LLD §3.4 | `Cli.php::HELP_TEXT` | `CliTest::testSuccess[help]`, P07 | Verified |
| CONV-24 | No crash | HLD §6 | `src/bin/dp-hello`, `Cli.php::run` | as NFR-03 | Verified |
| CONV-25 | Broken pipe | LLD §3.5 | `Output.php::write` | `OutputTest::testWriteToAnUnwritableStreamFailsQuietly` | Verified |
| CONV-26 | No stdin | HLD §1.1 | (stdin never read) | Inspection | Verified |
| CONV-31 | Memory above runtime baseline | HLD §7 | whole program | Master test plan §4.4 | Verified |

CONV-04, CONV-16, CONV-17, and CONV-19 … CONV-21 do not apply (no input data, files, or paths);
CONV-27 … CONV-30 describe the test harness.

## 3. Backward traceability (test → requirement)

| Test | Type | Requirement(s) verified |
| :-- | :-- | :-- |
| P01 | black-box, public | FR-01 |
| P02, P03, P04 | black-box, public | FR-02, CONV-08 |
| P05, P06 | black-box, public | FR-04, CONV-07 |
| P07 | black-box, public | FR-07, CONV-23 |
| P08 | black-box, public | FR-08, CONV-02 |
| P09 | black-box, public | FR-10, FR-11, CONV-13, CONV-14 |
| P10 | black-box, public | FR-09, FR-11 |
| `ArgumentParserTest::*` | unit | FR-02, FR-06, FR-07, FR-08, FR-09, FR-10, CONV-05, CONV-07 … CONV-13 |
| `ValidatorTest::*` | unit | FR-01, FR-03, FR-04, FR-05, FR-09, CONV-15 |
| `GreetingTest::*` | unit | FR-01, FR-02, FR-04, NFR-01, NFR-05 |
| `CliTest::*` | unit (end to end, in process) | FR-01 … FR-11, CONV-02, CONV-12 … CONV-14, CONV-23 |
| `OutputTest::*` | unit | CONV-03, CONV-25, NFR-03 |
| `LauncherTest::*` | integration (child process) | FR-02, FR-11, NFR-01, CONV-03, CONV-05 |

## 4. Coverage summary

| Category | Total | Designed | Implemented | Verified |
| :-- | :-- | :-- | :-- | :-- |
| User requirements (UR) | 12 | 12 | 12 | 12 |
| Functional (FR) | 11 | 11 | 11 | 11 |
| Non-functional (NFR) | 7 | 7 | 7 | 7 |
| Conventions (CONV, applicable) | 21 | 21 | 21 | 21 |

## 5. Gaps and justifications

None. NFR-02, NFR-04, NFR-07, CONV-06, and CONV-26 are verified by measurement or inspection, as
SRS §7 prescribes.
