#!/bin/bash
# compare every object's non-code section contents and all relocations
# between two builds (ELF; one objdump call per object and kind)
S=${SHARED_CORE_WORK_DIR:?set SHARED_CORE_WORK_DIR to a scratch directory}
O=${LLVM_OBJDUMP:-/opt/homebrew/opt/llvm/bin/llvm-objdump}
A=$1; B=$2
norm_relocs() { $O -r "$1" | grep -v 'file format' | sed -E 's/\.L[A-Za-z]*[0-9]+/.L/g'; }
data() { $O -s "$1" | awk '/^Contents of section / { keep = ($4 !~ /^\.text/ && $4 !~ /^\.comment/) } keep'; }
cd "$S/$A"
for o in $(find src -name '*.o' | sort); do
	[ "$(data $o | md5)" != "$(data $S/$B/$o | md5)" ] && echo "DATA DIFF $o"
	[ "$(norm_relocs $o | md5)" != "$(norm_relocs $S/$B/$o | md5)" ] && echo "RELOC DIFF $o"
done
true
