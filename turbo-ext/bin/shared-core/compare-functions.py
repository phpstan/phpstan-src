#!/usr/bin/env python3
# Per-function fingerprinting of arm64 Mach-O object files built against
# different PHP versions: identical source -> identical bytes + relocations
# unless a Zend header (struct layout, inline function, macro) differs.
import hashlib, os, re, subprocess, sys
from concurrent.futures import ThreadPoolExecutor

OBJDUMP = os.environ.get('LLVM_OBJDUMP', '/opt/homebrew/opt/llvm/bin/llvm-objdump')
func_re = re.compile(r'^<(.+)>:$')
addr_re = re.compile(r'0x[0-9a-f]+ <')


def fingerprints(obj):
	out = subprocess.run([OBJDUMP, '-d', '-r', '--no-leading-addr', '--no-show-raw-insn', obj],
		capture_output=True, text=True).stdout
	funcs = {}
	cur = None
	lines = []
	n = 0
	def flush():
		if cur is not None:
			funcs[cur] = (hashlib.sha1('\n'.join(lines).encode()).hexdigest(), n * 4)
	for line in out.splitlines():
		m = func_re.match(line)
		if m:
			flush()
			cur, lines, n = m.group(1).replace('16ZEND_RESULT_CODE', '11zend_result'), [], 0
			continue
		if cur is None:
			continue
		s = line.strip()
		if not s or s.startswith('Disassembly of section'):
			continue
		# branch targets print absolute section offsets; keep only the symbolic part
		s = addr_re.sub('<', s)
		s = re.sub(r'OUTLINED_FUNCTION_\d+', 'OUTLINED_FUNCTION', s)
		if s.startswith('adrp') or s.startswith('adr\t'):
			s = re.sub(r'<.*>', '', s)
		s = re.sub(r'\$_\d+', '$_', s)
		s = re.sub(r'\.cold\.\d+', '.cold', s)
		s = re.sub(r'(l_\.str|ltmp|LJTI|lJTI|l___const|lCPI)[\.\d_]+', r'\1', s)
		# 8.6 renamed enum ZEND_RESULT_CODE to zend_result: a different
		# mangling of the same type in our own functions' names
		s = s.replace('16ZEND_RESULT_CODE', '11zend_result')
		# local labels: GCC's .LC0, .LANCHOR1, .L42 and clang's .L.str.356 —
		# numbered in the order the translation unit declares them, so one
		# more string literal in a header renumbers every later one (their
		# contents are compared by compare-data-*.sh)
		s = re.sub(r'(\.L[A-Za-z_.]*?)\.?\d+', r'\1', s)
		# GCC addresses merged strings through the section of whichever
		# function emitted them first: section order, not content
		s = re.sub(r'\.rodata\.[^ +]*\.str1\.\d+(\+0x[0-9a-f]+)?', '.rodata.str', s)
		# relocation lines print an offset column too
		s = re.sub(r'^[0-9a-f]+:\s+', '', s)
		lines.append(s)
		if not s.startswith(('ARM64_RELOC', 'R_AARCH64', 'R_X86_64')):
			n += 1
	flush()
	return funcs


def collect(builddir):
	objs = []
	for root, _, files in os.walk(os.path.join(builddir, 'src')):
		objs += [os.path.join(root, f) for f in files if f.endswith('.o')]
	res = {}
	with ThreadPoolExecutor(10) as ex:
		for obj, funcs in zip(objs, ex.map(fingerprints, objs)):
			rel = os.path.relpath(obj, builddir)
			for name, v in funcs.items():
				res[(rel, name)] = v
	return res


def main():
	dirs = sys.argv[1:]
	data = [collect(d) for d in dirs]
	base = data[0]
	total = sum(sz for _, sz in base.values())
	print(f'{dirs[0]}: {len(base)} functions, {total/1048576:.2f} MB code')
	for d, other in zip(dirs[1:], data[1:]):
		same = diff = only = 0
		per_file = {}
		for k, (h, sz) in base.items():
			if k not in other:
				only += sz
				continue
			if other[k][0] == h:
				same += sz
			else:
				diff += sz
				per_file.setdefault(k[0], [0, 0])
				per_file[k[0]][0] += sz
				per_file[k[0]][1] += 1
		newonly = sum(sz for k, (_, sz) in other.items() if k not in base)
		print(f'vs {d}: identical {same/1048576:.2f} MB ({100*same/total:.1f}%), differing {diff/1048576:.2f} MB ({100*diff/total:.1f}%), '
			f'only in base {only/1048576:.2f} MB, only in other {newonly/1048576:.2f} MB')
		with open(f'diff-{os.path.basename(d)}.txt', 'w') as fh:
			for k, (h, sz) in sorted(base.items(), key=lambda x: -x[1][1]):
				if k in other and other[k][0] != h:
					fh.write(f'{sz:8d} {k[0]} {k[1]}\n')
		print('  top files by differing bytes:')
		for f, (sz, cnt) in sorted(per_file.items(), key=lambda x: -x[1][0])[:12]:
			print(f'    {f:50s} {sz/1024:8.1f} KB in {cnt} fns')
	# identical across all
	if len(data) > 2:
		allsame = sum(sz for k, (h, sz) in base.items() if all(k in o and o[k][0] == h for o in data[1:]))
		print(f'identical across all {len(dirs)}: {allsame/1048576:.2f} MB ({100*allsame/total:.1f}%)')


main()
