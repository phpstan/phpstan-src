#!/bin/bash
# The shared-core gate (phar.yml turbo-shared-core-gate, and locally): the
# core is compiled once per platform and runs on every supported PHP, so
# every source but the version-specific ones (the Makefile's SHIM_SOURCES)
# must compile to the same code against each version's headers — thread-safe
# ones included. Builds them all in the CI images with clang (whose output
# does not drift with header spelling the way GCC's register allocation
# does; the property proven is the source's, and the GCC build ships it),
# then fails on any function, data section or relocation that differs
# outside Abi.o, Shadow.o, TrustedTypes.o and main.o.
#
# Needs docker and an llvm-objdump (LLVM_OBJDUMP, else the newest found).
# Usage: SHARED_CORE_WORK_DIR=<scratch directory> check-linux.sh
set -euo pipefail

S=${SHARED_CORE_WORK_DIR:?set SHARED_CORE_WORK_DIR to a scratch directory}
HERE=$(cd "$(dirname "$0")" && pwd)
VERSIONS=${GATE_VERSIONS:-8.3 8.4 8.5 8.6 8.6-zts}
SHIM_OBJECTS='src/(Abi|Shadow|TrustedTypes|main)\.o'
if [ -z "${LLVM_OBJDUMP:-}" ]; then
	LLVM_OBJDUMP=$(command -v llvm-objdump || { ls /usr/bin/llvm-objdump-* /opt/homebrew/opt/llvm/bin/llvm-objdump 2>/dev/null || true; } | sort -V | tail -1)
fi
export LLVM_OBJDUMP
[ -x "$LLVM_OBJDUMP" ] || { echo "no llvm-objdump found (set LLVM_OBJDUMP)"; exit 2; }

# the CI build images plus clang, built once per run
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
GATE_MAKE_ARGS="CXX=clang++-14 PGO_FLAGS='-fgnuc-version=4.3 -U_FORTIFY_SOURCE'" \
	"$HERE/gate-linux.sh"

cd "$S"
first=$(echo "$VERSIONS" | awk '{print $1}')
failed=0
for v in $VERSIONS; do
	[ "$v" = "$first" ] && continue
	python3 -I "$HERE/compare-functions.py" "lx-$first" "lx-$v" > /dev/null
	functions=$(grep -v -E "$SHIM_OBJECTS" "diff-lx-$v.txt" || true)
	data=$("$HERE/compare-data-linux.sh" "lx-$first" "lx-$v" | grep -v -E "$SHIM_OBJECTS" || true)
	if [ -n "$functions$data" ]; then
		failed=1
		echo "::error::shared code compiles differently against PHP $first and PHP $v headers"
		[ -n "$functions" ] && echo "$functions" | while read -r size object name; do
			echo "  $object: $(echo "$name" | c++filt) ($size bytes)"
		done
		[ -n "$data" ] && echo "$data" | sed 's/^/  /'
	else
		echo "PHP $first vs PHP $v: shared code identical"
	fi
done
if [ "$failed" = 1 ]; then
	cat <<'EOF'
A source outside the version-specific ones reads something whose layout,
value or signature differs between PHP versions. Route it through pt_abi
(abi.h, filled by Abi.cpp) or move the code into a version-specific source;
turbo-ext/README.md ("Shared core") lists the mechanisms.
EOF
fi
exit "$failed"
