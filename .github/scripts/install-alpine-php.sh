#!/bin/sh
# Installs PHP $PHP_MINOR and the extension's build tools into the Alpine
# containers of the musl turbo legs in phar.yml. With PHP_ZTS=1 it builds a
# thread-safe PHP from the release tarball instead (build-php.sh): Alpine
# packages no thread-safe PHP, while the official php:*-alpine Docker images
# are thread-safe from 8.6 on.
set -eu

apk add --no-cache bash curl git make g++ musl-dev linux-headers patch tar zstd

if [ "${PHP_ZTS:-0}" = "1" ]; then
	apk add --no-cache pkgconf xz libxml2-dev oniguruma-dev curl-dev openssl-dev zlib-dev
	PHP_MINOR="$PHP_MINOR" PHP_ZTS=1 sh "$(dirname "$0")/../turbo-build/build-php.sh"
	exit 0
fi

V="$(echo "$PHP_MINOR" | tr -d .)"
# Alpine's php86 packages are currently only in edge/testing.
if [ "$PHP_MINOR" = "8.6" ]; then
	echo 'https://dl-cdn.alpinelinux.org/alpine/edge/testing' >> /etc/apk/repositories
fi
apk add --no-cache \
	"php$V" "php$V-dev" "php$V-ctype" "php$V-curl" "php$V-mbstring" \
	"php$V-tokenizer" "php$V-iconv" "php$V-openssl" "php$V-phar" \
	"php$V-dom" "php$V-xml" "php$V-xmlwriter" "php$V-simplexml"
ln -sf "/usr/bin/php$V" /usr/local/bin/php
ln -sf "/usr/bin/php-config$V" /usr/local/bin/php-config
