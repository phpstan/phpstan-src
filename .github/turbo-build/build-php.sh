#!/bin/sh
# Builds and installs the newest release of PHP $PHP_MINOR into /usr/local
# from the official tarball, thread-safe when $PHP_ZTS is 1. Used by the
# Dockerfile for the PHP builds ondrej/php does not ship (prerelease
# minors, and every thread-safe build), and by the musl ZTS legs in
# phar.yml, which run it in Alpine — Alpine packages no thread-safe PHP.
#
# The extensions match what the turbo-compile legs use from the PPA
# images: tokenizer for the parser tests, pcntl + posix for the fork
# tests, opcache (always built in since 8.5) for the trusted-types test,
# and curl, mbstring, openssl, xml and zlib for Composer.
set -eu

# The release is resolved from php.net at build time, so the weekly image
# rebuild and every musl leg pick up a new patch release (or the next
# release candidate of a prerelease minor) without a change here: an
# extension built against one patch release loads into every other of the
# minor. The sha256 published next to the tarball guards the download. A
# minor without a stable release yet is not in the releases API; its
# current release candidate is in the pre-release one, under <minor>.0.
php_net() {
	curl -fsSL --retry 3 "$1"
}
RELEASE_JSON="$(php_net "https://www.php.net/releases/index.php?json&version=$PHP_MINOR")"
PHP_VERSION="$(printf '%s' "$RELEASE_JSON" | jq -r '.version // empty')"
if [ -n "$PHP_VERSION" ]; then
	PHP_URL="https://www.php.net/distributions/php-$PHP_VERSION.tar.xz"
	PHP_SHA256="$(printf '%s' "$RELEASE_JSON" | jq -r --arg file "php-$PHP_VERSION.tar.xz" '.source[] | select(.filename == $file) | .sha256')"
else
	PRERELEASE_JSON="$(php_net "https://www.php.net/pre-release-builds.php?format=json" | jq --arg minor "$PHP_MINOR.0" '.[$minor].release // empty')"
	PHP_VERSION="$(printf '%s' "$PRERELEASE_JSON" | jq -r '.version // empty')"
	PHP_URL="$(printf '%s' "$PRERELEASE_JSON" | jq -r '.files.xz.path // empty')"
	PHP_SHA256="$(printf '%s' "$PRERELEASE_JSON" | jq -r '.files.xz.sha256 // empty')"
fi
case "$PHP_VERSION" in
	"$PHP_MINOR".*) ;;
	*)
		echo "build-php.sh: php.net lists no release of PHP $PHP_MINOR (resolved \"$PHP_VERSION\")" >&2
		exit 1
		;;
esac
if [ -z "$PHP_URL" ] || [ -z "$PHP_SHA256" ]; then
	echo "build-php.sh: php.net lists no tar.xz with a sha256 for PHP $PHP_VERSION" >&2
	exit 1
fi
echo "build-php.sh: building PHP $PHP_VERSION from $PHP_URL"
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
