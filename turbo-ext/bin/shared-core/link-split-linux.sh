#!/bin/bash
# The Linux counterpart of link-split-mac.sh, on the objects gate-linux.sh
# built: one core shared object linked from the $CORE_FROM objects, one thin
# extension per PHP version linked against it (found next to itself through
# $ORIGIN), each loaded in its version's CI image, which then runs the smoke
# test with it.
set -e
S=${SHARED_CORE_WORK_DIR:?set SHARED_CORE_WORK_DIR to a scratch directory}
REPO=$(cd "$(dirname "$0")/../../.." && pwd)
VERSIONS=${GATE_VERSIONS:-8.3 8.4 8.5 8.6}
CORE_FROM=${CORE_FROM:-8.4}
OUT=$S/split-linux
rm -rf "$OUT"; mkdir -p "$OUT"

SHIM='src/Abi.o src/Shadow.o src/TrustedTypes.o src/main.o src/TerminateHandler.o'
# the link flags of the Makefile's Linux build; --exclude-libs keeps the
# statically folded libstdc++ out of both dynamic symbol tables
FLAGS='-static-libstdc++ -static-libgcc -Wl,--exclude-libs,ALL -Wl,--gc-sections -Wl,-z,relro,-z,now -Wl,-z,noexecstack'

docker run --rm -v "$S/lx-$CORE_FROM:/build" -v "$OUT:/out" -w /build "ghcr.io/phpstan/turbo-build:gnu-php$CORE_FROM" sh -ec "
	core=\$(find src -name '*.o' | grep -v -E 'src/(Abi|Shadow|TrustedTypes|main)\.o\$' | sort)
	g++ -shared $FLAGS -Wl,-soname,libphpstan_turbo_core.so -o /out/libphpstan_turbo_core.so \$core
	echo \"core exports: \$(nm -D --defined-only /out/libphpstan_turbo_core.so | wc -l)\"
"

for v in $VERSIONS; do
	docker run --rm -v "$S/lx-$v:/build" -v "$OUT:/out" -v "$REPO:/repo:ro" -w /build "ghcr.io/phpstan/turbo-build:gnu-php$v" sh -ec "
		g++ -shared $FLAGS -Wl,-rpath,'\$ORIGIN' -o /out/phpstan_turbo-$v.so $SHIM -L/out -lphpstan_turbo_core
		# every extension symbol must come from the core: undefined pt_*
		# symbols would only fail at load time
		missing=\$(nm -D --undefined-only /out/phpstan_turbo-$v.so | awk '{print \$2}' | grep -E '^(_Z.*phpstanturbo|_Z.*pt_|pt_)' | while read sym; do nm -D --defined-only /out/libphpstan_turbo_core.so | awk '{print \$3}' | grep -qx \"\$sym\" || echo \$sym; done)
		[ -z \"\$missing\" ] || { echo \"PHP $v: undefined extension symbols: \$missing\"; exit 1; }
		cd /repo
		echo \"PHP $v: \$(php -n -d extension=/out/phpstan_turbo-$v.so -r 'echo PHP_VERSION, \" turbo=\", phpversion(\"phpstan_turbo\");')\"
		TURBO_DLL=/out/phpstan_turbo-$v.so php -d memory_limit=4G -d extension=/out/phpstan_turbo-$v.so turbo-ext/tests/smoke.php 2>&1 | tail -1
	" || echo "PHP $v: FAILED"
done
ls -l "$OUT"
