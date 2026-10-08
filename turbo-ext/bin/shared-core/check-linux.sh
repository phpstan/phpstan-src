#!/bin/bash
# The shared-core gate, locally: the core is compiled once per platform and
# runs on every supported PHP, so every source but the version-specific ones
# (the Makefile's SHIM_SOURCES) must compile to the same code against each
# version's headers — thread-safe ones included. Builds them all in the CI
# images with clang (whose output does not drift with header spelling the
# way GCC's register allocation does; the property proven is the source's,
# and the GCC build ships it), then fails on any function, data section or
# relocation that differs from the reference version's build outside Abi.o,
# Shadow.o, TrustedTypes.o and main.o.
#
# phar.yml's turbo-shared-core-gate runs the same two halves on separate
# machines: one fingerprint-linux.sh leg per version, then gate.py compare.
# The reference is the version the cores are compiled against
# (turbo-compile-core).
#
# Needs docker and an llvm-objdump (LLVM_OBJDUMP, else the newest found).
# Usage: SHARED_CORE_WORK_DIR=<scratch directory> [GATE_VERSIONS=...] [GATE_REFERENCE=8.5] check-linux.sh
set -euo pipefail

S=${SHARED_CORE_WORK_DIR:?set SHARED_CORE_WORK_DIR to a scratch directory}
HERE=$(cd "$(dirname "$0")" && pwd)
REFERENCE=${GATE_REFERENCE:-8.5}
VERSIONS=${GATE_VERSIONS:-8.3 8.4 8.5 8.6 8.3-zts 8.4-zts 8.5-zts 8.6-zts}
case " $VERSIONS " in
	*" $REFERENCE "*) ;;
	*) VERSIONS="$REFERENCE $VERSIONS" ;;
esac

rm -rf "$S/fingerprints"
GATE_VERSIONS="$VERSIONS" "$HERE/fingerprint-linux.sh"
python3 -I "$HERE/gate.py" compare "$REFERENCE" "$S/fingerprints"
