#!/usr/bin/env bash
set -euo pipefail

# setup-php supplies Composer, php.ini and CA certificates, but its 8.6
# nightly has no matching development pack. Replace the runtime and bundled
# extensions with the official prerelease, preserving that configuration.
PHP_DIR="$(cygpath -u "$(php -r 'echo dirname(PHP_BINARY);')")"
case "$MATRIX_TS" in
  zts) INFIX="" ;;
  nts) INFIX="-nts" ;;
  *) echo "Unknown thread-safety mode: $MATRIX_TS" >&2; exit 1 ;;
esac
PACK="php-$PHP86_WINDOWS_VERSION$INFIX-Win32-vs18-x64.zip"
ARCHIVE="$RUNNER_TEMP/$PACK"
curl -fsSLo "$ARCHIVE" "https://downloads.php.net/~windows/qa/$PACK" \
  || curl -fsSLo "$ARCHIVE" "https://downloads.php.net/~windows/qa/archives/$PACK"
unzip -qo "$ARCHIVE" -d "$PHP_DIR"
php -r '
  if (PHP_VERSION !== getenv("PHP86_WINDOWS_VERSION") || (bool) PHP_ZTS !== (getenv("MATRIX_TS") === "zts")) {
    fwrite(STDERR, "PHP prerelease version or thread-safety mismatch\n");
    exit(1);
  }
  echo "Using PHP ", PHP_VERSION, PHP_ZTS ? " ZTS\n" : " NTS\n";
'
