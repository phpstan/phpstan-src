#!/bin/sh
# Builds and installs PHP $PHP_MINOR into /usr/local from the official
# release tarball pinned below, thread-safe when $PHP_ZTS is 1. Used by the
# Dockerfile for the PHP builds ondrej/php does not ship (prerelease
# minors, and every thread-safe build), and by the musl ZTS legs in
# phar.yml, which run it in Alpine — Alpine packages no thread-safe PHP.
#
# The extensions match what the turbo-compile legs use from the PPA
# images: tokenizer for the parser tests, pcntl + posix for the fork
# tests, opcache (always built in since 8.5) for the trusted-types test,
# and curl, mbstring, openssl, xml and zlib for Composer.
set -eu

# The tarball for each minor this script builds; one pin shared by the
# glibc image and the musl legs, so both build the same release.
case "$PHP_MINOR" in
	8.6)
		PHP_VERSION=8.6.0RC2
		PHP_URL=https://downloads.php.net/~mbeccati/php-8.6.0RC2.tar.xz
		PHP_SHA256=ef3fba21c311e9bbace0e2102702446d322c275b8a10e6b33b28f2561299671c
		;;
	*)
		echo "build-php.sh: no tarball pinned for PHP $PHP_MINOR" >&2
		exit 1
		;;
esac
export PHP_VERSION
PHP_ZTS="${PHP_ZTS:-0}"
export PHP_ZTS
ZTS_FLAG=""
if [ "$PHP_ZTS" = "1" ]; then
	ZTS_FLAG="--enable-zts"
fi

cd /tmp
curl -fsSLo php.tar.xz "$PHP_URL"
echo "$PHP_SHA256  php.tar.xz" | sha256sum -c -
mkdir php-src
tar -xJf php.tar.xz -C php-src --strip-components=1
rm php.tar.xz

cd php-src
# shellcheck disable=SC2086 # ZTS_FLAG is empty or a single word
./configure \
	--prefix=/usr/local \
	--disable-cgi \
	--disable-phpdbg \
	--without-sqlite3 \
	--without-pdo-sqlite \
	--enable-mbstring \
	--enable-pcntl \
	--with-curl \
	--with-openssl \
	--with-zlib \
	$ZTS_FLAG
make -j"$(nproc)"
make install

# The distro CLI packages the other images use ship a production php.ini
# with no memory limit; match it.
INI_DIR="$(php -r 'echo PHP_CONFIG_FILE_PATH;')"
mkdir -p "$INI_DIR"
sed 's/^memory_limit = .*/memory_limit = -1/' php.ini-production > "$INI_DIR/php.ini"

cd /tmp
rm -rf php-src

php -r 'if (PHP_VERSION !== getenv("PHP_VERSION")) { fwrite(STDERR, "built PHP " . PHP_VERSION . ", expected " . getenv("PHP_VERSION") . "\n"); exit(1); }'
php -r 'if ((int) PHP_ZTS !== (int) getenv("PHP_ZTS")) { fwrite(STDERR, "built PHP_ZTS=" . PHP_ZTS . ", expected " . getenv("PHP_ZTS") . "\n"); exit(1); }'
php -m
