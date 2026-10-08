#!/bin/bash
# Link one core dylib (from the objects of $CORE_FROM) and one thin extension per PHP version.
set -e
S=${SHARED_CORE_WORK_DIR:?set SHARED_CORE_WORK_DIR to a scratch directory}
N=/opt/homebrew/opt/llvm/bin/llvm-nm
CORE_FROM=${CORE_FROM:-g-8.4}
OUT=$S/split; rm -rf $OUT; mkdir -p $OUT
SHIM_RE='src/(Abi|Shadow|TrustedTypes|main)\.o$'
cd $S/$CORE_FROM
CORE=$(find src -name '*.o' | grep -v -E "$SHIM_RE" | sort)
SHIM="src/Abi.o src/Shadow.o src/TrustedTypes.o src/main.o"
# the boundary, both directions
$N -u $SHIM | grep -v ':$' | awk '{print $NF}' | sort -u > $OUT/shim-undef
$N --defined-only $CORE | grep -E ' [TDBSC] ' | awk '{print $3}' | sort -u > $OUT/core-def
comm -12 $OUT/shim-undef $OUT/core-def > $OUT/core.exp
$N -u $CORE | grep -v ':$' | awk '{print $NF}' | sort -u > $OUT/core-undef
$N --defined-only $SHIM | grep -E ' [TDBSC] ' | awk '{print $3}' | sort -u > $OUT/shim-def
comm -12 $OUT/core-undef $OUT/shim-def > $OUT/shim.exp
echo _get_module >> $OUT/shim.exp
c++ -shared -undefined dynamic_lookup -Wl,-dead_strip \
	-install_name @loader_path/libphpstan_turbo_core.dylib \
	-Wl,-exported_symbols_list,$OUT/core.exp \
	-o $OUT/libphpstan_turbo_core.dylib $CORE
for v in 8.3 8.4 8.5; do
	cd $S/g-$v
	c++ -shared -undefined dynamic_lookup -Wl,-dead_strip \
		-Wl,-exported_symbols_list,$OUT/shim.exp \
		-o $OUT/phpstan_turbo-$v.so $SHIM $OUT/libphpstan_turbo_core.dylib
done
ls -l $OUT/*.dylib $OUT/*.so
