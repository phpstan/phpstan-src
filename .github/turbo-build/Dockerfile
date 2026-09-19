# Prebuilt image for the turbo-compile gnu legs in phar.yml, published to
# ghcr.io/phpstan/turbo-build by turbo-build-image.yml (tags gnu-php8.3,
# gnu-php8.4, gnu-php8.5; each a linux/amd64 + linux/arm64 manifest).
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

# The source label links the GHCR package to this repository.
LABEL org.opencontainers.image.source="https://github.com/phpstan/phpstan-src"
LABEL org.opencontainers.image.description="PHP and build toolchain for compiling the phpstan_turbo extension (glibc floor 2.34)"

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
	&& add-apt-repository -y ppa:ondrej/php \
	&& apt_install "php$PHP_MINOR-cli" "php$PHP_MINOR-dev" "php$PHP_MINOR-curl" "php$PHP_MINOR-mbstring" "php$PHP_MINOR-xml" \
	&& rm -rf /var/lib/apt/lists/* \
	&& ln -sf "/usr/bin/php$PHP_MINOR" /usr/local/bin/php \
	&& ln -sf "/usr/bin/php-config$PHP_MINOR" /usr/local/bin/php-config
