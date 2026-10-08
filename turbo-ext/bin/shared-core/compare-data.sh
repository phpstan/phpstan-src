#!/bin/bash
# compare every non-code section (contents + relocations) of each object between two builds
S=${SHARED_CORE_WORK_DIR:?set SHARED_CORE_WORK_DIR to a scratch directory}
O=/opt/homebrew/opt/llvm/bin/llvm-objdump
A=$1; B=$2
cd $S/$A
for o in $(find src -name '*.o' | sort); do
	sects=$($O -h $o | awk 'NR>3 && $2 !~ /__text|__compact_unwind|__eh_frame/ && $2 != "" {print $2}' | sort -u)
	for sec in $sects; do
		a=$($O -s -j "$sec" $o 2>/dev/null | tail -n +3 | md5)
		b=$($O -s -j "$sec" $S/$B/$o 2>/dev/null | tail -n +3 | md5)
		[ "$a" != "$b" ] && echo "DATA DIFF $o $sec"
	done
	ra=$($O -r $o | grep -v 'file format' | sed -E 's/OUTLINED_FUNCTION_[0-9]+/OF/; s/l_\.str[.0-9]*/l_.str/; s/lCPI[0-9_]+/lCPI/; s/ltmp[0-9]+/ltmp/; s/\$_[0-9]+/$_/' | md5)
	rb=$($O -r $S/$B/$o | grep -v 'file format' | sed -E 's/OUTLINED_FUNCTION_[0-9]+/OF/; s/l_\.str[.0-9]*/l_.str/; s/lCPI[0-9_]+/lCPI/; s/ltmp[0-9]+/ltmp/; s/\$_[0-9]+/$_/' | md5)
	[ "$ra" != "$rb" ] && echo "RELOC DIFF $o"
done
