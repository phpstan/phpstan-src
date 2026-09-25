#!/bin/sh
# Builds and installs PHP $PHP_VERSION into /usr/local from the official
# release tarball at $PHP_URL, verified against $PHP_SHA256. Used by the
# Dockerfile for the PHP minors ondrej/php does not ship yet.
#
# The extensions match what the turbo-compile legs use from the PPA
# images: tokenizer for the parser tests, pcntl + posix for the fork
# tests, opcache (always built in since 8.5) for the trusted-types test,
# and curl, mbstring, openssl, xml and zlib for Composer.
set -eu

cd /tmp
curl -fsSLo php.tar.xz "$PHP_URL"
echo "$PHP_SHA256  php.tar.xz" | sha256sum -c -
mkdir php-src
tar -xJf php.tar.xz -C php-src --strip-components=1
rm php.tar.xz

cd php-src
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
	--with-zlib
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
php -m
