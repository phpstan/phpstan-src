#!/bin/bash
# The Linux counterpart of gate-mac.sh: compiles turbo-ext in the CI build
# images (ghcr.io/phpstan/turbo-build:gnu-php<v>, GCC 11.4) against each
# PHP version's headers — LTO off, so the objects hold machine code — and
# compares the results function by function.
S=${SHARED_CORE_WORK_DIR:?set SHARED_CORE_WORK_DIR to a scratch directory}
WT=$(cd "$(dirname "$0")/../.." && pwd)
VERSIONS=${GATE_VERSIONS:-8.3 8.4 8.5 8.6}
JOBS=${GATE_JOBS:-3}
# GATE_PLATFORM (e.g. linux/amd64) runs the images of another architecture —
# code generation differs between them, and CI gates on x86_64
rm -f "$S"/lx-*.failed
for v in $VERSIONS; do
	mkdir -p "$S/lx-$v"
	rsync -a --exclude '*.o' --exclude '*.so' --exclude PHP-CPP --exclude pgo --exclude version.stamp "$WT/" "$S/lx-$v/"
	echo "${GATE_VERSION_TXT:-dev}" > "$S/lx-$v/VERSION.txt"
	docker run --rm ${GATE_PLATFORM:+--platform "$GATE_PLATFORM"} -v "$S/lx-$v:/build" -w /build "${GATE_IMAGE:-ghcr.io/phpstan/turbo-build}:gnu-php$v" \
		sh -c "make -k -j$JOBS LTO_FLAGS= WARN_FLAGS=\"${GATE_WARN_FLAGS:--Wall}\" ${GATE_MAKE_ARGS} ${GATE_TARGET:-objects} > build.log 2>&1" \
		|| { touch "$S/lx-$v.failed"; echo "BUILD FAILED $v"; grep -E ': (error|warning):' "$S/lx-$v/build.log" | head -20; } &
done
wait
ls "$S"/lx-*.failed >/dev/null 2>&1 && exit 1
first=$(echo $VERSIONS | awk '{print $1}')
cd "$S" && python3 -I "$WT/bin/shared-core/compare-functions.py" $(for v in $VERSIONS; do printf 'lx-%s ' $v; done)
