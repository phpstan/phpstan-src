#!/usr/bin/env python3
# The shared-core gate in two halves, so that each PHP version's build can
# run on its own machine (phar.yml turbo-shared-core-gate) and the
# comparison afterwards needs only the results:
#
#   gate.py fingerprint <build directory> > fingerprint-<version>.tsv
#       one line per function (normalized disassembly hash and size, see
#       compare-functions.py), per object's data sections and per object's
#       relocations, for every object under src/
#   gate.py compare <reference version> <fingerprint directory>
#       compares every fingerprint-<version>.tsv in the directory with the
#       reference version's and fails on any difference outside the
#       version-specific objects (the Makefile's SHIM_SOURCES)
import hashlib, importlib.util, os, re, subprocess, sys
from concurrent.futures import ThreadPoolExecutor

HERE = os.path.dirname(os.path.abspath(__file__))
OBJDUMP = os.environ.get('LLVM_OBJDUMP', '/opt/homebrew/opt/llvm/bin/llvm-objdump')
SHIM_OBJECTS = re.compile(r'^src/(Abi|Shadow|TrustedTypes|main)\.o$')
FINGERPRINT_RE = re.compile(r'^fingerprint-(.+)\.tsv$')
# sections that are not data: the code is compared function by function,
# the rest is the object's own bookkeeping
NON_DATA_SECTION = re.compile(r'^\.(text|comment|strtab|shstrtab|symtab)')


def load_compare_functions():
	spec = importlib.util.spec_from_file_location('compare_functions', os.path.join(HERE, 'compare-functions.py'))
	module = importlib.util.module_from_spec(spec)
	spec.loader.exec_module(module)
	return module


def objdump(*args):
	return subprocess.run([OBJDUMP, *args], capture_output=True, text=True, check=True).stdout


def digest(lines):
	return hashlib.sha1('\n'.join(lines).encode()).hexdigest()


def data_digest(obj):
	# the contents of every non-code section
	keep = False
	lines = []
	for line in objdump('-s', obj).replace('16ZEND_RESULT_CODE', '11zend_result').splitlines():
		if line.startswith('Contents of section '):
			keep = not NON_DATA_SECTION.match(line.split()[3])
		if keep:
			lines.append(line)
	return digest(lines)


def relocation_digest(obj):
	lines = []
	for line in objdump('-r', obj).splitlines():
		if 'file format' in line:
			continue
		line = re.sub(r'(\.L[A-Za-z_.]*)\.?[0-9]+', r'\1', line)
		lines.append(line.replace('16ZEND_RESULT_CODE', '11zend_result'))
	return digest(lines)


def fingerprint(builddir):
	cf = load_compare_functions()
	objects = sorted(
		os.path.relpath(os.path.join(root, f), builddir)
		for root, _, files in os.walk(os.path.join(builddir, 'src'))
		for f in files if f.endswith('.o')
	)
	def one(rel):
		obj = os.path.join(builddir, rel)
		return rel, cf.fingerprints(obj), data_digest(obj), relocation_digest(obj)
	with ThreadPoolExecutor(os.cpu_count() or 4) as ex:
		for rel, functions, data, relocations in ex.map(one, objects):
			for name, (h, size) in sorted(functions.items()):
				print(f'F\t{rel}\t{name}\t{h}\t{size}')
			print(f'D\t{rel}\t{data}')
			print(f'R\t{rel}\t{relocations}')


def read(path):
	functions, data, relocations = {}, {}, {}
	with open(path) as fh:
		for line in fh:
			kind, rel, *rest = line.rstrip('\n').split('\t')
			if kind == 'F':
				functions[(rel, rest[0])] = (rest[1], int(rest[2]))
			elif kind == 'D':
				data[rel] = rest[0]
			elif kind == 'R':
				relocations[rel] = rest[0]
	return functions, data, relocations


def demangle(names):
	try:
		return subprocess.run(['c++filt'], input='\n'.join(names), capture_output=True, text=True, check=True).stdout.splitlines()
	except (OSError, subprocess.CalledProcessError):
		return names


def differences(reference, other):
	ref_functions, ref_data, ref_relocations = reference
	functions, data, relocations = other
	found = []
	for key in sorted(ref_functions.keys() | functions.keys()):
		rel, name = key
		if SHIM_OBJECTS.match(rel):
			continue
		if key not in functions:
			found.append((rel, name, f'only in the reference build ({ref_functions[key][1]} bytes)'))
		elif key not in ref_functions:
			found.append((rel, name, f'only in this build ({functions[key][1]} bytes)'))
		elif functions[key][0] != ref_functions[key][0]:
			found.append((rel, name, f'{ref_functions[key][1]} bytes'))
	for kind, ref, cur in (('data sections', ref_data, data), ('relocations', ref_relocations, relocations)):
		for rel in sorted(ref.keys() | cur.keys()):
			if not SHIM_OBJECTS.match(rel) and ref.get(rel) != cur.get(rel):
				found.append((rel, None, f'{kind} differ'))
	return found


def compare(reference_version, directory):
	builds = {}
	for f in sorted(os.listdir(directory)):
		m = FINGERPRINT_RE.match(f)
		if m:
			builds[m.group(1)] = read(os.path.join(directory, f))
	if reference_version not in builds:
		print(f'::error::no fingerprint-{reference_version}.tsv in {directory}')
		return 2
	if len(builds) < 2:
		print(f'::error::nothing to compare the PHP {reference_version} build with in {directory}')
		return 2
	failed = False
	for version in sorted(builds):
		if version == reference_version:
			continue
		found = differences(builds[reference_version], builds[version])
		if not found:
			print(f'PHP {reference_version} vs PHP {version}: shared code identical')
			continue
		failed = True
		print(f'::error::shared code compiles differently against PHP {reference_version} and PHP {version} headers')
		names = demangle([name or '' for _, name, _ in found])
		for (rel, _, what), name in zip(found, names):
			print(f'  {rel}: {name + " " if name else ""}({what})')
	if failed:
		print('''A source outside the version-specific ones reads something whose layout,
value or signature differs between PHP versions. Route it through pt_abi
(abi.h, filled by Abi.cpp) or move the code into a version-specific source;
turbo-ext/README.md ("Shared core") lists the mechanisms.''')
	return 1 if failed else 0


def main():
	if len(sys.argv) == 3 and sys.argv[1] == 'fingerprint':
		fingerprint(sys.argv[2])
		return 0
	if len(sys.argv) == 4 and sys.argv[1] == 'compare':
		return compare(sys.argv[2], sys.argv[3])
	print('usage: gate.py fingerprint <build directory> | gate.py compare <reference version> <fingerprint directory>', file=sys.stderr)
	return 2


sys.exit(main())
