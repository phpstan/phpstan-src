#!/bin/sh
# Installs PHP $PHP_MINOR and the extension's build tools into the Alpine
# containers of the musl turbo legs in phar.yml. It builds PHP from the
# release tarball instead (build-php.sh) with PHP_ZTS=1 — Alpine packages
# no thread-safe PHP, while the official php:*-alpine Docker images are
# thread-safe from 8.6 on — and for a minor the Alpine release no longer
# packages (8.2).
#
# That build takes minutes, so with $PHP_CACHE_DIR set the PHP it installs
# into /usr/local (where nothing else lives in the Alpine images) is kept
# there as php.tar, and installed from there when the directory already has
# it. phar.yml keeps the directory in actions/cache under the key
# `install-alpine-php.sh --resolve` prints: the Alpine release, the
# architecture, the PHP release build-php.sh resolves and the thread
# safety, so a new patch release (or release candidate) or a new Alpine
# snapshot builds again. --resolve installs no PHP and prints
# `release=<key>` when this leg builds PHP, `release=` when it installs
# Alpine's packages.
set -eu

apk add --no-cache bash curl git make g++ musl-dev linux-headers patch tar zstd >&2

V="$(echo "$PHP_MINOR" | tr -d .)"
# Alpine's php86 packages are currently only in edge/testing.
EDGE_TESTING='https://dl-cdn.alpinelinux.org/alpine/edge/testing'
if [ "$PHP_MINOR" = "8.6" ] && ! grep -qx "$EDGE_TESTING" /etc/apk/repositories; then
	echo "$EDGE_TESTING" >> /etc/apk/repositories
fi

BUILD_PHP="$(dirname "$0")/../turbo-build/build-php.sh"
BUILDS_PHP=0
if [ "${PHP_ZTS:-0}" = "1" ] || [ -z "$(apk search -q -x "php$V")" ]; then
	BUILDS_PHP=1
fi

if [ "${1:-}" = "--resolve" ]; then
	if [ "$BUILDS_PHP" = "0" ]; then
		echo "release="
		exit 0
	fi
	apk add --no-cache jq >&2
	RELEASE="$(PHP_MINOR="$PHP_MINOR" PHP_RESOLVE_ONLY=1 sh "$BUILD_PHP")"
	ZTS_SUFFIX=""
	if [ "${PHP_ZTS:-0}" = "1" ]; then
		ZTS_SUFFIX="-zts"
	fi
	echo "release=alpine$(cat /etc/alpine-release)-$(uname -m)-php$RELEASE$ZTS_SUFFIX"
	exit 0
fi

if [ "$BUILDS_PHP" = "1" ]; then
	apk add --no-cache jq pkgconf xz libxml2-dev oniguruma-dev curl-dev openssl-dev zlib-dev
	if [ -n "${PHP_CACHE_DIR:-}" ] && [ -f "$PHP_CACHE_DIR/php.tar" ]; then
		if tar -xf "$PHP_CACHE_DIR/php.tar" -C /usr/local && php -r 'exit((int) PHP_ZTS === (int) getenv("PHP_ZTS") ? 0 : 1);'; then
			echo "install-alpine-php.sh: installed PHP $(php -r 'echo PHP_VERSION;') from $PHP_CACHE_DIR"
			exit 0
		fi
		echo "install-alpine-php.sh: the PHP in $PHP_CACHE_DIR does not run here; building it again" >&2
	fi
	PHP_MINOR="$PHP_MINOR" PHP_ZTS="${PHP_ZTS:-0}" sh "$BUILD_PHP"
	if [ -n "${PHP_CACHE_DIR:-}" ]; then
		mkdir -p "$PHP_CACHE_DIR"
		tar -cf "$PHP_CACHE_DIR/php.tar" -C /usr/local .
	fi
	exit 0
fi

apk add --no-cache \
	"php$V" "php$V-dev" "php$V-ctype" "php$V-curl" "php$V-mbstring" \
	"php$V-tokenizer" "php$V-iconv" "php$V-openssl" "php$V-phar" \
	"php$V-dom" "php$V-xml" "php$V-xmlwriter" "php$V-simplexml"
ln -sf "/usr/bin/php$V" /usr/local/bin/php
ln -sf "/usr/bin/php-config$V" /usr/local/bin/php-config
