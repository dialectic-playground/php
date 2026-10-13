# Toolchain image for dialectic-playground/php: PHP (container-first; see tools/dp-run).
#
# PHP 8.5; build and package tool: Composer.
# Official base image pinned by multi-architecture index digest, plus make and python3 (for the
# curriculum's black-box runner). Dependabot (docker) refreshes the digest; moving to a new
# release line is a deliberate change (keep `make toolchain` and the native CI in step).
FROM docker.io/library/php:8.5-cli-trixie@sha256:01a109229f4465bc9ef042d9198f09a4d9da7775a825dbd8572dcdbd8756c4d3

COPY --from=docker.io/library/composer:2@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac /usr/bin/composer /usr/local/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends make python3 git unzip \
    && rm -rf /var/lib/apt/lists/*

ENV COMPOSER_HOME=/dp-cache/composer \
    DP_IN_CONTAINER=1
