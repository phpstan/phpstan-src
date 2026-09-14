#!/bin/sh
# The training run of `make pgo`: PHPStan analyses its own analyser, rule
# and type sources with the instrumented extension loaded, so the recorded
# profile reflects the real workload (Type-kernel and scope operations
# dominate). Runs from the monorepo checkout with Composer dependencies
# installed; a single analyser process (--debug) so one process writes the
# whole profile (forked workers _exit() without writing theirs).
#
# The extension is loaded through an ini scan directory rather than `-d
# extension=`: bin/phpstan may re-exec itself to enable OPcache, and the
# environment survives that restart while -d flags do not.
set -e

EXT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
ROOT="$(cd "$EXT_DIR/.." && pwd)"
SO="$EXT_DIR/phpstan_turbo.so"
PGO_DIR="${PGO_DIR:-pgo}"
case "$PGO_DIR" in /*) ;; *) PGO_DIR="$EXT_DIR/$PGO_DIR" ;; esac

[ -f "$SO" ] || { echo "pgo-train: $SO is missing — build the instrumented extension first" >&2; exit 1; }
[ -f "$ROOT/vendor/autoload.php" ] || { echo "pgo-train: $ROOT/vendor is missing — run composer install first" >&2; exit 1; }

mkdir -p "$PGO_DIR"
INI_DIR="$(mktemp -d)"
trap 'rm -rf "$INI_DIR"' EXIT
printf 'extension=%s\n' "$SO" > "$INI_DIR/phpstan-turbo-pgo.ini"
# a leading empty entry keeps the default scan directory (system ini files)
export PHP_INI_SCAN_DIR="${PHP_INI_SCAN_DIR:-}:$INI_DIR"
# clang's instrumented runtime; ignored by a GCC build (which writes .gcda
# files next to the objects at exit)
export LLVM_PROFILE_FILE="$PGO_DIR/turbo-%p.profraw"

cd "$ROOT"
REPORTED="$(php -r 'echo phpversion("phpstan_turbo");')"
EXPECTED="$(sed -n "s/.*EXPECTED_EXTENSION_VERSION = '\([^']*\)'.*/\1/p" src/Turbo/TurboExtensionEnabler.php)"
if [ "$REPORTED" != "$EXPECTED" ]; then
	echo "pgo-train: the instrumented extension reports '$REPORTED' but the enabler expects '$EXPECTED' — the training run would leave it inactive" >&2
	exit 1
fi

php bin/phpstan clear-result-cache --no-interaction -q
php -d memory_limit=4G bin/phpstan analyse src/Analyser src/Rules src/Type --debug --no-progress --no-interaction -q --error-format=raw > /dev/null || true

if [ "$(php -r 'echo (int) (phpversion("phpstan_turbo") !== false);')" != "1" ]; then
	echo "pgo-train: the extension did not load in the training process" >&2
	exit 1
fi
if ls "$PGO_DIR"/*.profraw > /dev/null 2>&1; then
	echo "pgo-train: recorded $(ls "$PGO_DIR"/*.profraw | wc -l | tr -d ' ') clang profile(s) in $PGO_DIR"
elif find "$EXT_DIR/src" -name '*.gcda' | grep -q .; then
	echo "pgo-train: recorded $(find "$EXT_DIR/src" -name '*.gcda' | wc -l | tr -d ' ') GCC profile files next to the objects"
else
	echo "pgo-train: no profile was written — was the extension built with the instrumentation flags?" >&2
	exit 1
fi
