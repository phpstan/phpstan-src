#!/bin/bash
# One half of the shared-core gate (check-linux.sh runs both): compiles
# turbo-ext with clang in the CI build image of each PHP version in
# GATE_VERSIONS — thread-safe ones are "<minor>-zts" — and writes
# $SHARED_CORE_WORK_DIR/fingerprints/fingerprint-<version>.tsv (gate.py
# fingerprint) for each. phar.yml's turbo-shared-core-gate legs run it with
# one version each; gate.py compare then needs only the .tsv files.
#
# Needs docker and an llvm-objdump (LLVM_OBJDUMP, else the newest found).
# Usage: SHARED_CORE_WORK_DIR=<scratch directory> [GATE_VERSIONS="8.4 8.6-zts"] fingerprint-linux.sh
set -euo pipefail

S=${SHARED_CORE_WORK_DIR:?set SHARED_CORE_WORK_DIR to a scratch directory}
HERE=$(cd "$(dirname "$0")" && pwd)
VERSIONS=${GATE_VERSIONS:-8.3 8.4 8.5 8.6 8.3-zts 8.4-zts 8.5-zts 8.6-zts}
if [ -z "${LLVM_OBJDUMP:-}" ]; then
	LLVM_OBJDUMP=$(command -v llvm-objdump || { ls /usr/bin/llvm-objdump-* /opt/homebrew/opt/llvm/bin/llvm-objdump 2>/dev/null || true; } | sort -V | tail -1)
fi
export LLVM_OBJDUMP
[ -x "$LLVM_OBJDUMP" ] || { echo "no llvm-objdump found (set LLVM_OBJDUMP)"; exit 2; }

# the CI build images plus clang
for v in $VERSIONS; do
	printf 'FROM ghcr.io/phpstan/turbo-build:gnu-php%s\nRUN apt-get update -qq && DEBIAN_FRONTEND=noninteractive apt-get install -y -qq clang-14 > /dev/null && rm -rf /var/lib/apt/lists/*\n' "$v" \
		| docker build -q ${GATE_PLATFORM:+--platform "$GATE_PLATFORM"} -t "turbo-build-clang:gnu-php$v" - > /dev/null
done

# -fgnuc-version=4.3: the headers before 8.6 enable ZEND_COLD for GCC 4.3+
# only and clang reports 4.2 by default; -U_FORTIFY_SOURCE: glibc's fortify
# wrappers then expect GCC builtins clang lacks. Neither matters to the
# property — they keep the header sets' compiler-identity branches alike.
GATE_IMAGE=turbo-build-clang \
GATE_VERSIONS="$VERSIONS" \
GATE_SUMMARY=0 \
GATE_MAKE_ARGS="CXX=clang++-14 PGO_FLAGS='-fgnuc-version=4.3 -U_FORTIFY_SOURCE'" \
	"$HERE/gate-linux.sh"

mkdir -p "$S/fingerprints"
for v in $VERSIONS; do
	python3 -I "$HERE/gate.py" fingerprint "$S/lx-$v" > "$S/fingerprints/fingerprint-$v.tsv"
	echo "PHP $v: $(grep -c '^F' "$S/fingerprints/fingerprint-$v.tsv") functions fingerprinted"
done
