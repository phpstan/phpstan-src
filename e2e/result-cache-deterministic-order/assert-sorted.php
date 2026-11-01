<?php declare(strict_types = 1);

// Fails if an entry section of the result cache, or the packages of a file, are not sorted.

$path = $argv[1] ?? null;
if ($path === null || !is_file($path)) {
	fwrite(STDERR, sprintf("Result cache %s does not exist.\n", $path ?? '<missing argument>'));
	exit(1);
}

$handle = fopen($path, 'r');
if ($handle === false) {
	fwrite(STDERR, sprintf("Cannot open %s.\n", $path));
	exit(1);
}

fgets($handle); // the PHP prefix line that keeps the file inert when included

$unsorted = [];
$sections = 0;
$packageLists = [];
while (($header = fgets($handle)) !== false) {
	$header = rtrim($header, "\n");
	if ($header === '') {
		continue;
	}

	$parts = explode(' ', $header, 2);
	if (count($parts) !== 2) {
		fwrite(STDERR, sprintf("Malformed frame header \"%s\".\n", $header));
		exit(1);
	}

	[$name, $size] = $parts;
	if (!str_ends_with($name, '*')) {
		// a single value, or (with #) payloads described by the index frame before them
		if ((int) $size > 0) {
			fread($handle, (int) $size);
		}

		continue;
	}

	$name = substr($name, 0, -1);
	$keys = [];
	for ($i = 0; $i < (int) $size; $i++) {
		[$keyLength, $valueLength] = array_map('intval', explode(' ', rtrim((string) fgets($handle), "\n"), 2));
		$key = unserialize((string) fread($handle, $keyLength));
		$value = unserialize((string) fread($handle, $valueLength));
		$keys[] = $key;
		if ($name !== 'packageDependencies') {
			continue;
		}

		$packageLists[$key] = $value;
	}

	$sections++;
	$sorted = $keys;
	sort($sorted);
	if ($keys === $sorted) {
		continue;
	}

	$unsorted[$name] = $keys;
}

fclose($handle);

if ($sections === 0) {
	fwrite(STDERR, "The result cache has no entry sections, so nothing was checked.\n");
	exit(1);
}

if ($packageLists === []) {
	fwrite(STDERR, "The result cache recorded no package dependencies, so nothing was checked.\n");
	exit(1);
}

$problems = [];
foreach ($unsorted as $name => $keys) {
	$problems[] = sprintf('Section "%s" is not in key order: %s', $name, implode(', ', array_map(static fn (int|string $key): string => basename((string) $key), $keys)));
}

foreach ($packageLists as $file => $packages) {
	$sortedPackages = $packages;
	sort($sortedPackages, SORT_STRING);
	if ($packages === $sortedPackages) {
		continue;
	}

	$problems[] = sprintf('The packages of %s are not in order: %s', basename($file), implode(', ', $packages));
}

if ($problems !== []) {
	fwrite(STDERR, implode("\n", $problems) . "\n");
	exit(1);
}

echo sprintf(
	"All %d entry sections of the result cache are in key order, and so are the packages of all %d files that have package dependencies.\n",
	$sections,
	count($packageLists),
);
