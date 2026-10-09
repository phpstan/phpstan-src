#!/usr/bin/env bash
set -euo pipefail

# setup-php supplies Composer, php.ini and CA certificates, but for an
# unreleased minor it installs a nightly build, which has no matching
# development pack. Replace the runtime and bundled extensions with the
# official prerelease find-php-windows-prereleases.sh resolved for this run
# (PHP_PRERELEASE, its JSON entry for the minor), preserving that
# configuration.
PHP_DIR="$(cygpath -u "$(php -r 'echo dirname(PHP_BINARY);')")"
case "$MATRIX_TS" in
  zts) TS=ts ;;
  nts) TS=nts ;;
  *) echo "Unknown thread-safety mode: $MATRIX_TS" >&2; exit 1 ;;
esac
# shellcheck disable=SC2016 # PHP code, not shell expansions
read -r VERSION PACK SHA256 < <(php -r '
  $entry = json_decode(getenv("PHP_PRERELEASE"), true, 512, JSON_THROW_ON_ERROR);
  $zip = $entry[$argv[1]]["zip"];
  echo $entry["version"], " ", $zip["path"], " ", $zip["sha256"], "\n";
' "$TS")
ARCHIVE="$RUNNER_TEMP/$PACK"
curl -fsSLo "$ARCHIVE" "https://downloads.php.net/~windows/qa/$PACK" \
  || curl -fsSLo "$ARCHIVE" "https://downloads.php.net/~windows/qa/archives/$PACK"
echo "$SHA256  $ARCHIVE" | sha256sum -c -
unzip -qo "$ARCHIVE" -d "$PHP_DIR"
EXPECTED_VERSION="$VERSION" php -r '
  if (PHP_VERSION !== getenv("EXPECTED_VERSION") || (bool) PHP_ZTS !== (getenv("MATRIX_TS") === "zts")) {
    fwrite(STDERR, "PHP prerelease version or thread-safety mismatch\n");
    exit(1);
  }
  echo "Using PHP ", PHP_VERSION, PHP_ZTS ? " ZTS\n" : " NTS\n";
'
