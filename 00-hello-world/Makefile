# 00-hello-world: Standard Terminal Hello World, in PHP.
#
# Extends the universal build contract in shared/templates/BASE-MAKEFILE:
#
#   make all | install | uninstall | test | check | lint | format | clean | distclean
#   make test-hidden | grade | help
#
# Layout: src/bin/dp-hello is the `#!/usr/bin/env php` launcher (BIN_DIR), src/lib holds the
# library (namespace DialecticPlayground\Hello, PSR-4), and src/tests the PHPUnit tests. The
# tool needs nothing but php at run time; Composer installs only the locked development tools
# (PHPUnit, PHPStan, PHP-CS-Fixer) into vendor/. Tools run as `php vendor/bin/<tool>`, which
# also works on Windows (Git Bash) without the .bat shims.
#
# Install: src/bin and src/lib go to $(LIBDIR)/php/00-hello-world, and a `#!/bin/sh` launcher
# that runs the installed bin/dp-hello with php (absolute path baked in) goes to
# $(BINDIR)/dp-hello. Uninstall removes both.
#
# Container-first: goals run in the toolchain container built from the repository's
# Containerfile (podman, then docker, then local; override with DP_RUNTIME).

PROJECT := 00-hello-world
TOOLS   := dp-hello

# Path to the sdlc-curriculum checkout (the specs, test suites, and runner live there).
CURRICULUM_ROOT ?= ../sdlc-curriculum

PHP      ?= php
COMPOSER ?= composer

# Directory that contains the built executables after `make all`.
BIN_DIR := src/bin

# Where `make install` puts the payload (see the header).
INSTALL_DIR = $(LIBDIR)/php/$(PROJECT)

# composer.lock is authoritative; `install` never re-resolves it and is quick when vendor/
# is current.
COMPOSER_INSTALL = $(COMPOSER) install --no-interaction --no-progress --quiet
PHP_SOURCES = find src -name '*.php' -print; echo src/bin/dp-hello; echo .php-cs-fixer.dist.php
PHP_CS_FIXER = $(PHP) vendor/bin/php-cs-fixer

DP_BUILD = chmod +x $(BIN_DIR)/dp-hello

define DP_UNIT_TEST
$(COMPOSER_INSTALL)
$(PHP) vendor/bin/phpunit
endef

define DP_LINT
$(COMPOSER_INSTALL)
{ $(PHP_SOURCES); } | while read -r file; do $(PHP) -l "$$file" >/dev/null || exit 1; done
$(PHP) vendor/bin/phpstan analyse --no-progress --memory-limit=1G
$(PHP_CS_FIXER) check --diff --show-progress=none
endef

define DP_FORMAT
$(COMPOSER_INSTALL)
$(PHP_CS_FIXER) fix --show-progress=none
endef

define DP_INSTALL
rm -rf "$(INSTALL_DIR)"
mkdir -p "$(INSTALL_DIR)" "$(BINDIR)"
cp -R src/bin src/lib "$(INSTALL_DIR)/"
printf '#!/bin/sh\nexec php %s "$$@"\n' "'$(INSTALL_DIR)/bin/dp-hello'" > "$(BINDIR)/dp-hello"
chmod +x "$(BINDIR)/dp-hello"
"$(BINDIR)/dp-hello" --version
endef

define DP_UNINSTALL
rm -f "$(BINDIR)/dp-hello"
rm -rf "$(INSTALL_DIR)"
rmdir "$(LIBDIR)/php" "$(LIBDIR)" 2>/dev/null || true
endef

# Tool caches (PHPUnit, PHPStan, PHP-CS-Fixer) and coverage output.
DP_CLEAN = rm -rf .phpunit.cache .phpstan.cache .php-cs-fixer.cache coverage

# The Composer-installed development tools.
DP_DISTCLEAN = rm -rf vendor

include $(CURRICULUM_ROOT)/shared/templates/BASE-MAKEFILE
