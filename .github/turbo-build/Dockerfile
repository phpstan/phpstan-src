# Prebuilt image for the turbo-compile gnu legs in phar.yml, published to
# ghcr.io/phpstan/turbo-build by turbo-build-image.yml (tags gnu-php8.3,
# gnu-php8.4, gnu-php8.5, gnu-php8.6; each a linux/amd64 + linux/arm64
# manifest).
#
# Baking the PHP toolchain in keeps apt and the ondrej/php PPA out of the
# compile jobs entirely: ports.ubuntu.com (the only Ubuntu arm64 mirror,
# no CDN in front of it) has repeated outages that failed the arm64 legs,
# and the from-scratch PPA setup cost ~1 minute on every x86_64 leg.
#
# The ubuntu:22.04 base pins the built binaries' glibc symbol floor at
# 2.34 (RHEL 9, Ubuntu 22.04+, Debian 12+) independently of the runner
# image — same floor as the previous `container: ubuntu:22.04` legs.
FROM ubuntu:26.04

ARG PHP_MINOR
ENV DEBIAN_FRONTEND=noninteractive

# PHP 8.6 is a prerelease, and ondrej/php trails its tags by weeks: on
# 2026-09-25 it still served 8.6.0beta3, whose module API (20250926) the
# 8.6.0RC2 that the php:8.6-rc images ship had already replaced
# (20260924) — an extension built against one refuses to load into the
# other. The 8.6 image therefore builds the official release tarball on
# the same base. Drop these and the source branch below once ondrej/php
# ships 8.6.0.
ARG PHP86_VERSION=8.6.0RC2
ARG PHP86_URL=https://downloads.php.net/~mbeccati/php-8.6.0RC2.tar.xz
ARG PHP86_SHA256=ef3fba21c311e9bbace0e2102702446d322c275b8a10e6b33b28f2561299671c

# The source label links the GHCR package to this repository.
LABEL org.opencontainers.image.source="https://github.com/phpstan/phpstan-src"
LABEL org.opencontainers.image.description="PHP and build toolchain for compiling the phpstan_turbo extension (glibc floor 2.34)"

COPY build-php.sh /tmp/build-php.sh

# apt mirrors 404 mid-sync (the fetched index can reference a superseded
# .deb already pruned from the pool); only a fresh index fixes that, so
# update+install retries as a unit
RUN test -n "$PHP_MINOR"; \
	apt_install() { \
		for attempt in 1 2 3; do \
			if apt-get update && apt-get install -y "$@"; then \
				return 0; \
			fi; \
			echo "apt-get failed (attempt $attempt), retrying with a fresh index"; \
			sleep 15; \
		done; \
		return 1; \
	}; \
	apt_install software-properties-common gnupg ca-certificates curl git make g++ patch unzip zstd \
	&& if [ "$PHP_MINOR" = "8.6" ]; then \
		apt_install pkg-config xz-utils libxml2-dev libonig-dev libcurl4-openssl-dev libssl-dev zlib1g-dev \
		&& PHP_VERSION="$PHP86_VERSION" PHP_URL="$PHP86_URL" PHP_SHA256="$PHP86_SHA256" sh /tmp/build-php.sh; \
	else \
		add-apt-repository -y ppa:ondrej/php \
		&& apt_install "php$PHP_MINOR-cli" "php$PHP_MINOR-dev" "php$PHP_MINOR-curl" "php$PHP_MINOR-mbstring" "php$PHP_MINOR-xml" \
		&& ln -sf "/usr/bin/php$PHP_MINOR" /usr/local/bin/php \
		&& ln -sf "/usr/bin/php-config$PHP_MINOR" /usr/local/bin/php-config; \
	fi \
	&& rm -rf /var/lib/apt/lists/* /tmp/build-php.sh
