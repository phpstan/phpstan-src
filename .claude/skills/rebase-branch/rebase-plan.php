<?php declare(strict_types = 1);

// Read-only report that prepares a rebase of a feature branch onto a fresh base
// tip: both sides' commits, commits the base already contains, files both sides
// touch, the conflicts a merge of the two tips predicts, and the turbo twins
// (#[ShadowedByTurboExtension] PHP class <-> turbo-ext .cpp) whose other half
// needs a port during the replay.
//
// Usage: php rebase-plan.php <new-base-ref> [<feature-ref>=HEAD]
// Run from anywhere inside the repository. Changes nothing.

if ($argc < 2) {
	fwrite(STDERR, "Usage: php rebase-plan.php <new-base-ref> [<feature-ref>]\n");
	exit(2);
}

function git(string ...$args): string
{
	$command = 'git ' . implode(' ', array_map('escapeshellarg', $args)) . ' 2>/dev/null';
	return rtrim((string) shell_exec($command), "\n");
}

/** @return list<string> */
function gitLines(string ...$args): array
{
	$out = git(...$args);
	return $out === '' ? [] : explode("\n", $out);
}

function short(string $sha): string
{
	return substr($sha, 0, 10);
}

$newBase = git('rev-parse', '--verify', $argv[1] . '^{commit}');
$tip = git('rev-parse', '--verify', ($argv[2] ?? 'HEAD') . '^{commit}');
if ($newBase === '' || $tip === '') {
	fwrite(STDERR, "Cannot resolve the given refs.\n");
	exit(2);
}
$oldBase = git('merge-base', $newBase, $tip);

echo "OLD BASE  ", short($oldBase), ' ', git('log', '-1', '--format=%s (%cs)', $oldBase), "\n";
echo "NEW BASE  ", short($newBase), ' ', git('log', '-1', '--format=%s (%cs)', $newBase), "\n";
echo "TIP       ", short($tip), ' ', git('log', '-1', '--format=%s (%cs)', $tip), "\n";

$featureCommits = gitLines('log', '--reverse', '--format=%H %s', $oldBase . '..' . $tip);
$baseCommits = gitLines('log', '--reverse', '--format=%H %s', $oldBase . '..' . $newBase);
echo "\nFeature commits: ", count($featureCommits), ", base commits since OLD BASE: ", count($baseCommits), "\n";
if ($oldBase === $newBase) {
	echo "The branch is already on top of the base tip - nothing to rebase.\n";
	exit(0);
}

echo "\n## Base commits since OLD BASE (merges omitted)\n";
$baseNoMerges = gitLines('log', '--reverse', '--no-merges', '--format=%h %s', $oldBase . '..' . $newBase);
foreach (array_slice($baseNoMerges, 0, 80) as $line) {
	echo "  $line\n";
}
if (count($baseNoMerges) > 80) {
	echo '  ... ', count($baseNoMerges) - 80, " more\n";
}

$merges = gitLines('log', '--merges', '--format=%h %s', $oldBase . '..' . $tip);
if ($merges !== []) {
	echo "\n## Merge commits in the feature range (a rebase linearizes them - decide first)\n";
	foreach ($merges as $line) {
		echo "  $line\n";
	}
}

// --cherry-mark prefixes '=' to commits whose patch the other side already has
echo "\n## Feature commits the base already contains\n";
$found = false;
foreach (gitLines('log', '--right-only', '--cherry-mark', '--no-merges', '--format=%m %h %s', $newBase . '...' . $tip) as $line) {
	if ($line[0] === '=') {
		echo "  patch-identical: ", substr($line, 2), "\n";
		$found = true;
	}
}
$baseSubjects = [];
foreach ($baseCommits as $line) {
	$baseSubjects[substr($line, 41)] = substr($line, 0, 10);
}
foreach ($featureCommits as $line) {
	$subject = substr($line, 41);
	if (isset($baseSubjects[$subject]) && $subject !== 'Bump expected turbo version') {
		echo "  same subject:    ", substr($line, 0, 10), " $subject  (base ", $baseSubjects[$subject], ")\n";
		$found = true;
	}
}
if (!$found) {
	echo "  none\n";
}

$bumps = array_values(array_filter($featureCommits, static fn (string $line): bool => substr($line, 41) === 'Bump expected turbo version'));
if ($bumps !== []) {
	echo "\n## Turbo version bump commits on the feature branch (drop them, run `make bump-turbo` once at the end)\n";
	foreach ($bumps as $line) {
		echo '  ', substr($line, 0, 10), "\n";
	}
}

$baseFiles = gitLines('diff', '--name-only', '--no-renames', $oldBase, $newBase);
$featureFiles = gitLines('diff', '--name-only', '--no-renames', $oldBase, $tip);
$baseFileSet = array_fill_keys($baseFiles, true);
$featureFileSet = array_fill_keys($featureFiles, true);

echo "\n## Files changed on both sides (", count(array_intersect_key($baseFileSet, $featureFileSet)), ")\n";
foreach ($featureFiles as $file) {
	if (!isset($baseFileSet[$file])) {
		continue;
	}
	$b = count(gitLines('log', '--format=%h', $oldBase . '..' . $newBase, '--', $file));
	$f = gitLines('log', '--reverse', '--format=%h', $oldBase . '..' . $tip, '--', $file);
	echo "  $file  (base: $b commits; feature: ", implode(' ', $f), ")\n";
}

// merge-tree of the two tips: conflicts of the final result. Individual commits
// can conflict where the final result does not (and the other way around).
echo "\n## Conflicts predicted by merging the two tips\n";
$mergeTree = gitLines('merge-tree', '--write-tree', '--name-only', '--no-messages', $newBase, $tip);
array_shift($mergeTree);
foreach ($mergeTree as $file) {
	if ($file !== '') {
		echo "  $file\n";
	}
}
if ($mergeTree === [] || $mergeTree === ['']) {
	echo "  none\n";
}

/**
 * Class name => [php path or vendor package note, cpp path]
 * @return array<string, array{php: ?string, cpp: string}>
 */
function shadowMap(string $rev): array
{
	$map = [];
	foreach (gitLines('grep', '-e', 'ShadowedByTurboExtension(', $rev, '--', 'src') as $line) {
		$line = substr($line, strlen($rev) + 1);
		[$path, $content] = explode(':', $line, 2);
		if (preg_match("~implementation:\\s*__DIR__\\s*\\.\\s*'([^']+)'~", $content, $m) !== 1) {
			continue;
		}
		$parts = [];
		foreach (explode('/', dirname($path) . $m[1]) as $part) {
			if ($part === '..') {
				array_pop($parts);
			} elseif ($part !== '.' && $part !== '') {
				$parts[] = $part;
			}
		}
		$class = 'PHPStan\\' . str_replace('/', '\\', substr($path, 4, -4));
		$map[$class] = ['php' => $path, 'cpp' => implode('/', $parts)];
	}

	$collector = git('show', $rev . ':build/PHPStan/Build/TurboAttributeCollector.php');
	if (preg_match('~VENDORED_PAIRS = \[(.*?)\];~s', $collector, $block) === 1) {
		preg_match_all("~([\\w\\\\]+)::class => \\[.*?'(turbo-ext/src/[^']+)'~", $block[1], $pairs, PREG_SET_ORDER);
		foreach ($pairs as $pair) {
			$class = ltrim($pair[1], '\\');
			if (!str_contains($class, '\\') && preg_match('~^use ([\\w\\\\]+\\\\' . $class . ');~m', $collector, $use) === 1) {
				$class = $use[1];
			}
			$map['vendor:' . $class] = ['php' => null, 'cpp' => $pair[2]];
		}
	}

	return $map;
}

$old = shadowMap($oldBase);
$new = shadowMap($newBase);
$feature = shadowMap($tip);

echo "\n## Turbo twins\n";
echo '  shadowed at OLD BASE: ', count($old), ', NEW BASE: ', count($new), ', TIP: ', count($feature), "\n";
echo '  new on base: ', count(array_diff_key($new, $old)), ', new on feature: ', count(array_diff_key($feature, $old)), ', unshadowed on base: ', count(array_diff_key($old, $new)), ', unshadowed on feature: ', count(array_diff_key($old, $feature)), "\n";
echo "  (rows below: only twins with work to do)\n";

// the vendored twin changes when the package that autoloads it changes version
function vendorPackage(string $rev, string $class): string
{
	static $locks = [];
	$locks[$rev] ??= json_decode(git('show', $rev . ':composer.lock'), true) ?? [];
	$best = '';
	$found = 'not in composer.lock';
	foreach (array_merge($locks[$rev]['packages'] ?? [], $locks[$rev]['packages-dev'] ?? []) as $package) {
		foreach (array_keys($package['autoload']['psr-4'] ?? []) as $prefix) {
			if (str_starts_with($class, $prefix) && strlen($prefix) > strlen($best)) {
				$best = $prefix;
				$found = $package['name'] . ' ' . $package['version'];
			}
		}
	}

	return $found;
}
$classes = array_keys($old + $new + $feature);
sort($classes);
$rows = [];
foreach ($classes as $class) {
	$pair = $feature[$class] ?? $new[$class] ?? $old[$class];
	$phpPaths = array_unique(array_filter([$old[$class]['php'] ?? null, $new[$class]['php'] ?? null, $feature[$class]['php'] ?? null]));
	$cppPaths = array_unique(array_filter([$old[$class]['cpp'] ?? null, $new[$class]['cpp'] ?? null, $feature[$class]['cpp'] ?? null]));
	$changed = static function (array $paths, array $set): bool {
		foreach ($paths as $path) {
			if (isset($set[$path])) {
				return true;
			}
		}
		return false;
	};
	if ($pair['php'] === null) {
		$vendorClass = substr($class, strlen('vendor:'));
		$phpB = vendorPackage($oldBase, $vendorClass) !== vendorPackage($newBase, $vendorClass);
		$phpF = vendorPackage($oldBase, $vendorClass) !== vendorPackage($tip, $vendorClass);
	} else {
		$phpB = $changed($phpPaths, $baseFileSet);
		$phpF = $changed($phpPaths, $featureFileSet);
	}
	$cppB = $changed($cppPaths, $baseFileSet);
	$cppF = $changed($cppPaths, $featureFileSet);
	$inOld = isset($old[$class]);
	$inNew = isset($new[$class]);
	$inTip = isset($feature[$class]);

	$actions = [];
	$commitsTouching = static fn (string $range, array $paths): string => $paths === [] ? '' : implode(' ', gitLines('log', '--reverse', '--format=%h', $range, '--', ...$paths));
	if (!$inOld && $inNew && $inTip) {
		$actions[] = 'SHADOWED ON BOTH SIDES INDEPENDENTLY - reconcile the two ports by hand';
	} elseif (!$inOld && $inNew) {
		if ($phpF) {
			$actions[] = 'NEW ON BASE - port the feature\'s PHP changes into ' . $pair['cpp'] . ' in feature commits ' . $commitsTouching($oldBase . '..' . $tip, $phpPaths);
		}
	} elseif (!$inOld && $inTip) {
		if ($phpB) {
			$actions[] = 'NEW ON FEATURE - port base commits ' . $commitsTouching($oldBase . '..' . $newBase, $phpPaths) . ' into ' . $pair['cpp'] . ' at feature commit ' . explode(' ', $commitsTouching($oldBase . '..' . $tip, $cppPaths))[0] . ' (introduces the .cpp)';
		}
	} elseif ($inOld && (!$inNew || !$inTip)) {
		$actions[] = 'UNSHADOWED on ' . (!$inNew ? 'base' : 'feature') . ' - the other side\'s .cpp edits are moot, delete on modify/delete';
	} else {
		if (($phpB || $cppB) && ($phpF || $cppF)) {
			$actions[] = 'BOTH SIDES CHANGED - the replayed .cpp must mirror the merged .php (feature commits ' . $commitsTouching($oldBase . '..' . $tip, array_merge($phpPaths, $cppPaths)) . ')';
		}
		if ($phpB && !$cppB && $pair['php'] !== null) {
			$actions[] = 'base changed the .php without the .cpp - check the base did not leave a mirror gap';
		}
		if ($phpF && !$cppF && $pair['php'] !== null) {
			$actions[] = 'feature changed the .php without the .cpp - check the feature did not leave a mirror gap';
		}
	}
	if ($actions === []) {
		continue;
	}
	$rows[] = sprintf("  %s\n    %s <-> %s  [old:%s new:%s tip:%s | php B:%s F:%s | cpp B:%s F:%s]\n    - %s\n",
		$class,
		$pair['php'] ?? vendorPackage($tip, substr($class, strlen('vendor:'))),
		$pair['cpp'],
		$inOld ? 'y' : '-', $inNew ? 'y' : '-', $inTip ? 'y' : '-',
		$phpB ? 'y' : '-', $phpF ? 'y' : '-', $cppB ? 'y' : '-', $cppF ? 'y' : '-',
		implode("\n    - ", $actions),
	);
}
echo $rows === [] ? "  no twin needs attention\n" : implode('', $rows);

// Native code reaches PHP members by name (method/property sites, class
// names in string literals) and copies constants; a rename, removal or value
// change on one side is invisible to git on the other side's .cpp files.
/** @return array{removed: list<string>, constants: list<string>, classes: list<string>} */
function vanishedNames(string $from, string $to): array
{
	$minus = [];
	$plus = [];
	$constants = [];
	foreach (gitLines('diff', '-U0', '--no-renames', $from, $to, '--', 'src') as $line) {
		if ($line === '' || ($line[0] !== '-' && $line[0] !== '+') || str_starts_with($line, '---') || str_starts_with($line, '+++')) {
			continue;
		}
		$names = [];
		if (preg_match('~function\s+(\w+)\s*\(~', $line, $m) === 1) {
			$names[] = $m[1];
		}
		if (preg_match('~^[-+]\s*(?:(?:public|protected|private|readonly|static)(?:\(set\))?\s+)+[^=;(]*?\$(\w+)~', $line, $m) === 1) {
			$names[] = $m[1];
		}
		if (preg_match('~\bconst\s+(?:\w+\s+)?(\w+)\s*=~', $line, $m) === 1) {
			$constants[$m[1]][$line[0]] = true;
			$names[] = $m[1];
		}
		foreach ($names as $name) {
			if ($line[0] === '-') {
				$minus[$name] = true;
			} else {
				$plus[$name] = true;
			}
		}
	}
	$changedConstants = [];
	foreach ($constants as $name => $signs) {
		if (count($signs) === 2) {
			$changedConstants[] = $name;
		}
	}
	$classes = [];
	foreach (gitLines('diff', '--name-only', '--no-renames', '--diff-filter=D', $from, $to, '--', 'src') as $path) {
		$classes[] = 'PHPStan\\' . str_replace('/', '\\', substr($path, 4, -4));
	}

	return ['removed' => array_keys(array_diff_key($minus, $plus)), 'constants' => $changedConstants, 'classes' => $classes];
}

/** @param list<string> $patterns */
function grepTurbo(string $rev, array $patterns): array
{
	if ($patterns === []) {
		return [];
	}
	$args = ['grep', '-n', '-F'];
	foreach ($patterns as $pattern) {
		$args[] = '-e';
		$args[] = $pattern;
	}
	$args[] = $rev;
	$args[] = '--';
	$args[] = 'turbo-ext/src';
	$args[] = ':!turbo-ext/src/parser/ParserRunnerActions*';

	return array_map(static fn (string $line): string => substr($line, strlen($rev) + 1), gitLines(...$args));
}

$sides = [
	['base', $oldBase, $newBase, $tip, 'the feature\'s turbo-ext (TIP)'],
	['feature', $oldBase, $tip, $newBase, 'the base\'s turbo-ext (NEW BASE)'],
];
echo "\n## Names that vanished or changed on one side and are spelled in the other side's C++\n";
$any = false;
foreach ($sides as [$label, $from, $to, $otherRev, $otherLabel]) {
	$names = vanishedNames($from, $to);
	$patterns = [];
	foreach ($names['removed'] as $name) {
		$patterns[] = '"' . $name . '"';
	}
	foreach ($names['constants'] as $name) {
		$patterns[] = $name;
	}
	foreach ($names['classes'] as $class) {
		$patterns[] = str_replace('\\', '\\\\', $class) . '"';
	}
	$hits = grepTurbo($otherRev, $patterns);
	if ($hits === []) {
		continue;
	}
	$any = true;
	echo "  removed/renamed or re-valued on $label, referenced in $otherLabel:\n";
	foreach (array_slice($hits, 0, 60) as $hit) {
		echo "    $hit\n";
	}
	if (count($hits) > 60) {
		echo '    ... ', count($hits) - 60, " more\n";
	}
}
if (!$any) {
	echo "  none\n";
}

$infra = array_values(array_filter($featureFiles, static fn (string $f): bool => str_starts_with($f, 'turbo-ext/') && !str_starts_with($f, 'turbo-ext/src/generated/') && preg_match('~^turbo-ext/src/[A-Z]\w*\.cpp$~', $f) !== 1));
$newOnBase = array_diff_key($new, $old);
if ($newOnBase !== [] && $infra !== []) {
	echo "\n## Feature changed shared turbo-ext files; the ", count($newOnBase), " twins new on base may need adapting\n";
	foreach (array_slice($infra, 0, 40) as $f) {
		echo "  $f\n";
	}
	if (count($infra) > 40) {
		echo '  ... ', count($infra) - 40, " more\n";
	}
}
