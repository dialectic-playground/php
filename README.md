<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->

**Table of Contents** _generated with [DocToc](https://github.com/thlorenz/doctoc)_

- [php](#php)
  - [PHP at a glance](#php-at-a-glance)
  - [Toolchain](#toolchain)
  - [Layout](#layout)
  - [Development](#development)
  - [Implementing a project](#implementing-a-project)
  - [Contributing](#contributing)
  - [License](#license)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/dialectic-playground/.github/main/assets/php/dist/logo-dark.svg">
    <img alt="dialectic playground / php" src="https://raw.githubusercontent.com/dialectic-playground/.github/main/assets/php/dist/logo.svg" height="96">
  </picture>
</p>

# php

PHP implementation of the Dialectic Playground SDLC curriculum.

📖 **Documentation:** <https://dialectic-playground.github.io/docs/reference/languages/php/>

This repository implements the fifty-three projects of the
[SDLC curriculum](https://github.com/dialectic-playground/sdlc-curriculum) in PHP. Each project is
specified to the byte by the curriculum and verified by its black-box tests on Linux, macOS,
and Windows.

**Status:** Implemented projects: 00-hello-world

## PHP at a glance

| Aspect                  | PHP                                                |
| :---------------------- | :------------------------------------------------- |
| File extension          | `.php`                                             |
| CLI classification      | Included                                           |
| Image category          | First-Class                                        |
| Access to OS primitives | High (via php-cli and standard streams)            |
| Entry-point mechanism   | Engine wraps CLI context and exposes args via argv |

## Toolchain

|                      |                                                                                          |
| :------------------- | :--------------------------------------------------------------------------------------- |
| Release line         | PHP 8.5                                                                                  |
| Build / package tool | Composer                                                                                 |
| Container image      | `php:8.5-cli-trixie (+ composer:2)`, pinned by digest ([`Containerfile`](Containerfile)) |
| Native CI            | shivammathur/setup-php (8.5, Composer)                                                   |

`make toolchain` verifies that the container and native toolchains are on this release line
and that Composer is present.

Each project is a Composer project (`composer.json` + `composer.lock` in its directory) whose
locked development tools are installed into its `vendor/` by the make targets:

| Purpose    | Tool                                                | Make target   |
| :--------- | :-------------------------------------------------- | :------------ |
| Unit tests | PHPUnit                                             | `make test`   |
| Lint       | `php -l`, PHPStan (level max), PHP-CS-Fixer `check` | `make lint`   |
| Format     | PHP-CS-Fixer `fix`                                  | `make format` |

The tools run as `php vendor/bin/<tool>` (no `.bat` shims), and the versions are pinned exactly,
so the container and every native OS use the same ones. The tools themselves (`dp-hello`, …)
need only `php` at run time. They are `#!/usr/bin/env php` scripts: on Windows, run them from
Git Bash or MSYS2, which honor `#!` lines.

## Layout

| Path                                 | Contents                                                                                                          |
| :----------------------------------- | :---------------------------------------------------------------------------------------------------------------- |
| `00-hello-world/` … `52-spacecraft/` | One workspace per curriculum project: `Makefile` (with `DP_*` hooks), HLD, LLD, RTM, master test plan, and `src/` |
| `sdlc-curriculum/`                   | The curriculum, as a git submodule pinned to a release tag                                                        |
| `Makefile`                           | `make check` (repository, toolchain, implemented projects), `toolchain`, `projects`, `roundtrip`                  |
| `Containerfile`, `.devcontainer/`    | The toolchain image and the matching dev container                                                                |
| `tools/dp-run`, `tools/container.mk` | Shared container tooling (copies of the canonical files in `dialectic-playground/.github`)                        |
| `.github/workflows/ci.yml`           | CI: container and native (Linux, macOS, Windows) `make` targets, shared-tooling check, DCO                        |

## Development

Container-first: `make` targets and `tools/dp-run <command>` run in the toolchain container
via Podman, then Docker, then the local system. `DP_RUNTIME=podman|docker|local` overrides
the choice.

```sh
git clone --recurse-submodules https://github.com/dialectic-playground/php.git
cd php
make check                    # repository, toolchain, and every implemented project
DP_RUNTIME=local make check   # the same with your native toolchain
make -C 00-hello-world help   # a project's configuration and hooks
```

## Implementing a project

1. Complete the project's `hld.md` and `lld.md` before writing code.
2. Write the implementation and its unit tests under the project's `src/`, add the
   repository's Composer configuration, and fill in `BIN_DIR` and the `DP_*` hooks in
   the project's `Makefile`.
3. Pass `make -C <project> test` (container) and `DP_RUNTIME=local make -C <project> test`.
4. Add the project directory to `IMPLEMENTED` in the root `Makefile`. CI then builds, lints,
   and tests it in the container and natively on all three operating systems.
5. Keep `rtm.md` and `master-test-plan.md` current.

Specifications: [curriculum reference](https://dialectic-playground.github.io/docs/reference/curriculum/). Language setup:
[Set up a language repository](https://dialectic-playground.github.io/docs/how-to/set-up-a-language-repository/).

## Contributing

See the [organization contributing guide](https://github.com/dialectic-playground/.github/blob/main/CONTRIBUTING.md).
Pull requests are squash-merged, titles follow Conventional Commits, and every commit needs a
DCO sign-off.

## License

[MIT](LICENSE)
