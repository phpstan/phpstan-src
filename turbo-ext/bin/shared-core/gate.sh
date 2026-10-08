#!/bin/bash
# Build turbo-ext against PHP 8.3/8.4/8.5 headers (objects
# kept between runs for incremental builds) and compare per function.
S=${SHARED_CORE_WORK_DIR:?set SHARED_CORE_WORK_DIR to a scratch directory}
WT=$(cd "$(dirname "$0")/../.." && pwd)
rm -f "$S"/g-*.failed
for v in 8.3 8.4 8.5; do
	rsync -a --exclude '*.o' --exclude 'phpstan_turbo.so' --exclude PHP-CPP --exclude pgo --exclude version.stamp "$WT/" "$S/g-$v/"
	(cd "$S/g-$v" && make -k -j4 ${GATE_MAKE_ARGS} PHP_CONFIG=/opt/homebrew/opt/php@$v/bin/php-config ${GATE_TARGET:-phpstan_turbo.so} > build.log 2>&1 || { touch "$S/g-$v.failed"; echo "BUILD FAILED $v"; grep -E 'error' build.log; }) &
done
wait
ls "$S"/g-*.failed >/dev/null 2>&1 && exit 1
cd "$S" && python3 -I "$WT/bin/shared-core/compare-functions.py" g-8.3 g-8.4 g-8.5
