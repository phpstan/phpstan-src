<?php declare(strict_types = 1);

// Reads the framed result cache and fails if any of its entry sections is not in key order.
// The order the workers happen to finish in must not reach the file: a cache that is not
// reproducible cannot be hashed, compared or deduplicated between two machines.

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
		fread($handle, (int) $size);

		continue;
	}

	$name = substr($name, 0, -1);
	$keys = [];
	for ($i = 0; $i < (int) $size; $i++) {
		$length = (int) rtrim((string) fgets($handle), "\n");
		$entry = unserialize((string) fread($handle, $length));
		$key = (string) array_key_first($entry);
		$keys[] = $key;
		if ($name !== 'packageDependencies') {
			continue;
		}

		// The packages of one file are collected in the order its dependencies are reflected,
		// so their order has to be fixed too, not just the order of the files. Unlike the key
		// order, this does not depend on how the scheduler composes jobs, so it still fails
		// without the sort even when the whole project runs as a single job.
		$packageLists[$key] = $entry[array_key_first($entry)];
	}

	$sections++;
	$sorted = $keys;
	sort($sorted, SORT_STRING);
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
	$problems[] = sprintf('Section "%s" is not in key order: %s', $name, implode(', ', array_map('basename', $keys)));
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
