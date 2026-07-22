<?php declare(strict_types = 1);

/**
 * Verifies the shadowed PHP classes and their native C++ implementations are
 * in sync, pairing them by method name, driven by the
 * ShadowedByTurboExtension attributes (used by CI): every shadowed pair's
 * files exist, every public method of the PHP class has a PHP_METHOD
 * counterpart in the C++ file, and every PHP_METHOD corresponds to a method
 * of the PHP class. Vendored entries are skipped when vendor/ is not
 * installed.
 *
 * Usage: php turbo-ext/bin/side-by-side.php
 *
 * No dependencies — runs on any PHP >= 8.0 without vendor/.
 */

error_reporting(E_ALL);

$root = dirname(__DIR__, 2);
chdir($root);

/**
 * The manifest of shadowed pairs and the native class-map keys, derived from
 * the ShadowedByTurboExtension and ReferencedByTurboExtension attributes
 * under src/: the attributed file is the PHP side; ShadowedByTurboExtension
 * names the native class and the .cpp implementing it,
 * ReferencedByTurboExtension names the class-map key the native code
 * resolves the class through. Vendored classes cannot carry the attributes
 * and are hardcoded — the PhpParser\NodeTraverser pair here, and both in
 * build/generate-turbo-stubs.php, which derives the same data into
 * vendor/turbo-shadowed-classes.json and vendor/turbo-class-map.php with
 * runtime reflection instead of this textual scan (checkStructure() holds
 * the derivations against each other, and a class this scan misses still
 * fails --check: its .cpp or class-map key would have no attribute).
 *
 * @return array{
 *     manifest: array<string, array{php: string, cpp: string, vendored?: bool}>,
 *     referenced: array<string, string> class-map key => class name
 * }
 */
function scanAttributes(): array
{
	$manifest = [
		'PhpParser\NodeTraverser' => [
			'php' => 'vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php',
			'cpp' => 'turbo-ext/src/NodeTraverser.cpp',
			'vendored' => true,
		],
	];
	$referenced = [];

	foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator('src', FilesystemIterator::SKIP_DOTS)) as $file) {
		if ($file->getExtension() !== 'php') {
			continue;
		}
		$path = str_replace(DIRECTORY_SEPARATOR, '/', $file->getPathname());
		$contents = file_get_contents($path);
		if (!str_contains($contents, 'ByTurboExtension')) {
			continue;
		}

		// src/ is PSR-4 for the PHPStan namespace, so the class name follows
		// from the path
		$className = 'PHPStan\\' . strtr(substr($path, strlen('src/'), -strlen('.php')), '/', '\\');

		if (preg_match('~#\[ShadowedByTurboExtension\(\s*turboClass:\s*\'PHPStanTurbo\\\\\w+\',\s*implementation:\s*__DIR__\s*\.\s*\'([^\']+)\',?\s*\)\]~', $contents, $m) === 1) {
			// the __DIR__-relative implementation resolves against the
			// attributed file's directory
			$cpp = realpath(dirname($path) . $m[1]);
			$manifest[$className] = [
				'php' => $path,
				'cpp' => $cpp === false
					? dirname($path) . $m[1] // missing — analyzePair() reports it
					: str_replace(DIRECTORY_SEPARATOR, '/', substr($cpp, strlen((string) realpath('.')) + 1)),
			];
		}

		if (preg_match('~#\[ReferencedByTurboExtension\(key: \'(\w+)\'\)\]~', $contents, $m) === 1) {
			$referenced[$m[1]] = $className;
		}
	}

	ksort($manifest);
	ksort($referenced);

	return ['manifest' => $manifest, 'referenced' => $referenced];
}

['manifest' => $manifest, 'referenced' => $referenced] = scanAttributes();

/**
 * @return array<string, array{visibility: string, static: bool, startLine: int, endLine: int}>
 *         methods of the first class in the file, in source order
 */
function parsePhpMethods(string $file): array
{
	$tokens = token_get_all(file_get_contents($file));
	$methods = [];

	$line = 1;
	$depth = 0;
	$awaitingClassBrace = false;
	$inClass = false;
	$classDepth = 0;

	$declStartLine = null;
	$visibility = 'public';
	$static = false;
	$pendingMethod = null; // [name, visibility, static, startLine]
	$inMethodBody = false;
	$prevSignificant = null;

	foreach ($tokens as $token) {
		if (is_array($token)) {
			[$id, $text, $line] = $token;
		} else {
			$id = null;
			$text = $token;
		}

		if ($id === T_WHITESPACE || $id === T_COMMENT) {
			$line += substr_count($text, "\n");
			continue;
		}

		if ($id === T_CLASS && $prevSignificant !== T_DOUBLE_COLON && $prevSignificant !== T_NEW && !$inClass) {
			$awaitingClassBrace = true;
		}

		if ($text === '{' || $id === T_CURLY_OPEN || $id === T_DOLLAR_OPEN_CURLY_BRACES) {
			$depth++;
			if ($awaitingClassBrace) {
				$inClass = true;
				$classDepth = $depth;
				$awaitingClassBrace = false;
			} elseif ($pendingMethod !== null && !$inMethodBody && $depth === $classDepth + 1) {
				$inMethodBody = true;
			}
		} elseif ($text === '}') {
			$depth--;
			if ($inMethodBody && $depth === $classDepth) {
				$methods[$pendingMethod[0]] = [
					'visibility' => $pendingMethod[1],
					'static' => $pendingMethod[2],
					'startLine' => $pendingMethod[3],
					'endLine' => $line,
				];
				$pendingMethod = null;
				$inMethodBody = false;
				$declStartLine = null;
				$visibility = 'public';
				$static = false;
			} elseif ($inClass && $depth < $classDepth) {
				break; // first class only
			}
		} elseif ($inClass && !$inMethodBody && $depth === $classDepth) {
			if ($id === T_DOC_COMMENT || $id === T_ATTRIBUTE || $id === T_FINAL || $id === T_ABSTRACT
				|| $id === T_PUBLIC || $id === T_PROTECTED || $id === T_PRIVATE || $id === T_STATIC
				|| (defined('T_READONLY') && $id === T_READONLY) || $id === T_FUNCTION || $id === T_CONST || $id === T_VAR
			) {
				$declStartLine ??= $line;
			}
			if ($id === T_PUBLIC) {
				$visibility = 'public';
			} elseif ($id === T_PROTECTED) {
				$visibility = 'protected';
			} elseif ($id === T_PRIVATE) {
				$visibility = 'private';
			} elseif ($id === T_STATIC) {
				$static = true;
			} elseif ($prevSignificant === T_FUNCTION && $id !== null && $text !== '(') {
				// the method name — not necessarily T_STRING: names like
				// and()/or() tokenize as T_LOGICAL_AND/T_LOGICAL_OR
				$pendingMethod = [$text, $visibility, $static, $declStartLine ?? $line];
			} elseif ($text === ';') {
				if ($pendingMethod !== null) { // abstract/interface method
					$methods[$pendingMethod[0]] = [
						'visibility' => $pendingMethod[1],
						'static' => $pendingMethod[2],
						'startLine' => $pendingMethod[3],
						'endLine' => $line,
					];
					$pendingMethod = null;
				}
				$declStartLine = null;
				$visibility = 'public';
				$static = false;
			}
		}

		if ($id !== null) {
			$prevSignificant = $text === '&' ? $prevSignificant : $id; // skip & in "function &name"
			$line += substr_count($text, "\n");
		} else {
			$prevSignificant = $text;
		}
	}

	return $methods;
}

/**
 * @return array<string, array{startLine: int, endLine: int}>
 *         PHP_METHOD implementations, in source order
 */
function parseCppMethods(string $file): array
{
	$lines = file($file);
	$methods = [];

	// three anchor kinds, in preference order: the handle-class member that
	// mirrors the PHP twin (logic-to-logic), the legacy PHP_METHOD glue, and
	// the reg::Class registration site (glue lambda). Keyword-clashing names
	// carry a trailing underscore natively (and_/or_).
	$handleClassMembers = [];
	if (preg_match('/^namespace phpstanturbo \{$/m', file_get_contents($file)) === 1) {
		foreach ($lines as $i => $lineText) {
			if (preg_match('/^\t(?:static\s+)?(?:[\w:<>]+(?:\s*[&*])?\s+)+(\w+?)(_?)\(/', $lineText, $hm) === 1
				&& !str_starts_with(trim($lineText), 'return')
			) {
				$handleClassMembers[$hm[1]] ??= $i;
			}
		}
	}

	foreach ($lines as $i => $lineText) {
		if (
			preg_match('/^\s*(?:static\s+)?PHP_METHOD\(\s*\w+\s*,\s*(\w+)\s*\)/', $lineText, $m) !== 1
			&& preg_match('/^\s*(?:cls\.|\.)method\("(\w+)"/', $lineText, $m) !== 1
		) {
			continue;
		}
		// prefer the handle-class member of the same (or underscore-suffixed) name
		if (isset($handleClassMembers[$m[1]])) {
			$i = $handleClassMembers[$m[1]];
			$lineText = $lines[$i];
		}

		// include the contiguous comment block right above the method
		$start = $i;
		while ($start > 0) {
			$prev = trim($lines[$start - 1]);
			if ($prev === '' || (!str_starts_with($prev, '//') && !str_starts_with($prev, '/*') && !str_starts_with($prev, '*'))) {
				break;
			}
			$start--;
		}

		// find the matching closing brace, ignoring braces in strings/comments
		$depth = 0;
		$opened = false;
		$end = $i;
		$inBlockComment = false;
		for ($j = $i; $j < count($lines); $j++) {
			$text = $lines[$j];
			$len = strlen($text);
			$inString = null;
			for ($k = 0; $k < $len; $k++) {
				$c = $text[$k];
				if ($inBlockComment) {
					if ($c === '*' && ($text[$k + 1] ?? '') === '/') {
						$inBlockComment = false;
						$k++;
					}
					continue;
				}
				if ($inString !== null) {
					if ($c === '\\') {
						$k++;
					} elseif ($c === $inString) {
						$inString = null;
					}
					continue;
				}
				if ($c === '"' || $c === "'") {
					$inString = $c;
				} elseif ($c === '/' && ($text[$k + 1] ?? '') === '/') {
					break;
				} elseif ($c === '/' && ($text[$k + 1] ?? '') === '*') {
					$inBlockComment = true;
					$k++;
				} elseif ($c === '{') {
					$depth++;
					$opened = true;
				} elseif ($c === '}') {
					$depth--;
				}
			}
			if ($opened && $depth === 0) {
				$end = $j;
				break;
			}
		}

		$methods[$m[1]] = ['startLine' => $start + 1, 'endLine' => $end + 1];
	}

	return $methods;
}

/** @return array{skipped: string|null, phpMethods: array, cppMethods: array, missingNative: list<string>, orphanNative: list<string>} */
function analyzePair(string $className, array $entry): array
{
	if (!is_file($entry['php'])) {
		if ($entry['vendored'] ?? false) {
			return ['skipped' => sprintf('%s not present (vendor/ not installed)', $entry['php']), 'phpMethods' => [], 'cppMethods' => [], 'missingNative' => [], 'orphanNative' => []];
		}
		throw new RuntimeException(sprintf('%s: PHP file %s does not exist', $className, $entry['php']));
	}
	if (!is_file($entry['cpp'])) {
		throw new RuntimeException(sprintf('%s: C++ file %s does not exist', $className, $entry['cpp']));
	}

	$phpMethods = parsePhpMethods($entry['php']);
	$cppMethods = parseCppMethods($entry['cpp']);

	// Every public PHP method must exist natively — the stub subclass is
	// empty, so a missing native method is a fatal when the extension is on.
	$missingNative = [];
	foreach ($phpMethods as $name => $info) {
		if ($info['visibility'] === 'public' && !isset($cppMethods[$name])) {
			$missingNative[] = $name;
		}
	}

	// Every native method must correspond to a PHP method (any visibility) —
	// an orphan means the implementations drifted apart.
	$orphanNative = [];
	foreach ($cppMethods as $name => $info) {
		if (!isset($phpMethods[$name])) {
			$orphanNative[] = $name;
		}
	}

	return ['skipped' => null, 'phpMethods' => $phpMethods, 'cppMethods' => $cppMethods, 'missingNative' => $missingNative, 'orphanNative' => $orphanNative];
}

/**
 * The stub declarations of the generated vendor/turbo-stubs.php:
 * class name => native base name. Strict line-based parse — anything not
 * matching the generator's shape (empty one-line shells; a member declared
 * in a stub would exist only when the extension is loaded) is reported
 * through $problems, so a hand edit cannot slip past the structure check.
 *
 * @param list<string> $problems
 * @return array<string, string>
 */
function parseTurboStubs(string $file, array &$problems): array
{
	$stubs = [];
	$namespace = null;
	foreach (file($file) as $i => $line) {
		$line = rtrim($line, "\n");
		if ($line === '' || $line === '}' || str_starts_with($line, '<?php') || str_starts_with($line, '//')) {
			continue;
		}
		if (preg_match('~^namespace ([\w\\\\]+) \{$~', $line, $m) === 1) {
			$namespace = $m[1];
			continue;
		}
		if ($namespace !== null && preg_match('~^\t(?:final )?class (\w+) extends \\\\PHPStanTurbo\\\\(\w+) \{\}$~', $line, $m) === 1) {
			if ($m[1] !== $m[2]) {
				$problems[] = sprintf('%s:%d: class %s extends \PHPStanTurbo\%s — the names must match', $file, $i + 1, $m[1], $m[2]);
			}
			$stubs[$namespace . '\\' . $m[1]] = $m[2];
			continue;
		}
		$problems[] = sprintf('%s:%d: unexpected line %s — stubs must be empty shells declared as "class X extends \PHPStanTurbo\X {}"', $file, $i + 1, var_export($line, true));
	}

	return $stubs;
}

/**
 * The derived manifest must stay complete: every per-class .cpp file, every
 * stub in the generated vendor/turbo-stubs.php and every entry of the
 * generated vendor/turbo-shadowed-classes.json must correspond to a manifest
 * entry (and vice versa) — a shadowed class the attribute scan misses would
 * silently escape the parity and version-coupling checks.
 *
 * @return list<string> problems
 */
function checkStructure(array $manifest): array
{
	$problems = [];

	$fromManifest = [];
	foreach ($manifest as $className => $entry) {
		$fromManifest[basename($entry['cpp'], '.cpp')] = $className;
	}

	// main.cpp hosts extension-only classes (Runtime) that shadow no PHP
	// implementation — they never get a manifest entry.
	$cppClasses = array_values(array_diff(array_filter(array_map(
		static fn ($f) => preg_match('~PHP_METHOD\(PHPStanTurbo_|reg::Class\s+\w+\("PHPStanTurbo~', file_get_contents($f)) === 1 ? basename($f, '.cpp') : null,
		array_merge(glob('turbo-ext/src/*.cpp'), glob('turbo-ext/src/parser/*.cpp')),
	)), ['main']));
	foreach (array_diff($cppClasses, array_keys($fromManifest)) as $extra) {
		$problems[] = sprintf('%s from class-defining .cpp files (PHP_METHOD or reg::Class) has no ShadowedByTurboExtension attribute naming it', $extra);
	}
	foreach (array_diff(array_keys($fromManifest), $cppClasses) as $missing) {
		$problems[] = sprintf('shadowed class %s (%s) is missing from the class-defining .cpp files (PHP_METHOD or reg::Class)', $fromManifest[$missing], $missing);
	}

	// the generated files exist on any composer-installed checkout (the CI
	// structure check runs without vendor/) and must match the attributes
	// they were derived from — a mismatch means a stale autoloader dump
	if (is_file('vendor/turbo-stubs.php')) {
		$stubs = parseTurboStubs('vendor/turbo-stubs.php', $problems);
		foreach (array_diff(array_keys($stubs), array_keys($manifest)) as $extra) {
			$problems[] = sprintf('%s from vendor/turbo-stubs.php has no ShadowedByTurboExtension attribute — run composer dump-autoload', $extra);
		}
		foreach (array_diff(array_keys($manifest), array_keys($stubs)) as $missing) {
			$problems[] = sprintf('shadowed class %s is missing from vendor/turbo-stubs.php — run composer dump-autoload', $missing);
		}
	}
	if (is_file('vendor/turbo-shadowed-classes.json')) {
		$generated = json_decode(file_get_contents('vendor/turbo-shadowed-classes.json'), true, 8, JSON_THROW_ON_ERROR);
		if ($generated != $manifest) {
			$problems[] = 'vendor/turbo-shadowed-classes.json does not match the ShadowedByTurboExtension attributes — run composer dump-autoload';
		}
	}

	return $problems;
}

/**
 * The native class-reference table (pt_class_refs in support.cpp) and the
 * ReferencedByTurboExtension attributes must correspond: every table key
 * whose class lives in this repo must be claimed by exactly one attribute
 * (vendored PhpParser classes cannot carry it — the table bakes their names
 * as defaults, and the generator hardcodes their class-map entries), every
 * attribute key must exist in the table, and the generated
 * vendor/turbo-class-map.php (when present) must cover the table exactly.
 *
 * @param array<string, string> $referenced
 * @return list<string> problems
 */
function checkClassMap(array $referenced): array
{
	$problems = [];

	preg_match_all('~/\* PT_CLASS_\w+ \*/ \{"(\w+)", (NULL|"[^"]*")\}~', file_get_contents('turbo-ext/src/support.cpp'), $m, PREG_SET_ORDER);
	$tableKeys = [];
	foreach ($m as [, $key, $default]) {
		$tableKeys[$key] = $default;
		if ($default === 'NULL' || str_starts_with($default, '"PHPStan\\')) {
			if (!isset($referenced[$key])) {
				$problems[] = sprintf('pt_class_refs key %s (support.cpp) has no #[ReferencedByTurboExtension] attribute claiming it', $key);
			}
		}
	}

	foreach ($referenced as $key => $className) {
		if (isset($tableKeys[$key])) {
			continue;
		}
		$problems[] = sprintf('%s claims class-map key %s, which does not exist in pt_class_refs (support.cpp)', $className, $key);
	}

	if (is_file('vendor/turbo-class-map.php')) {
		$generatedMap = require 'vendor/turbo-class-map.php';
		foreach (array_diff_key($tableKeys, $generatedMap) as $key => $default) {
			$problems[] = sprintf('pt_class_refs key %s (support.cpp) is missing from vendor/turbo-class-map.php — hardcode the vendored entry in build/generate-turbo-stubs.php or run composer dump-autoload', $key);
		}
		foreach (array_diff_key($generatedMap, $tableKeys) as $key => $className) {
			$problems[] = sprintf('vendor/turbo-class-map.php entry %s (%s) does not exist in pt_class_refs (support.cpp)', $key, $className);
		}
		foreach ($referenced as $key => $className) {
			if (!isset($generatedMap[$key]) || $generatedMap[$key] === $className) {
				continue;
			}
			$problems[] = sprintf('vendor/turbo-class-map.php maps %s to %s, but the attribute sits on %s — run composer dump-autoload', $key, $generatedMap[$key], $className);
		}
	}

	return $problems;
}

/**
 * Every shadowed class needs differential coverage: its twin class name must
 * appear in one of the turbo-ext/tests/ scripts (smoke.php and
 * arena-smoke.php compare results method by method, parser-corpus.php
 * compares whole ASTs), so a new port cannot land untested.
 *
 * @return list<string> problems
 */
function checkSmokeCoverage(array $manifest): array
{
	$problems = [];
	$tests = '';
	foreach (glob('turbo-ext/tests/*.php') as $testFile) {
		$tests .= file_get_contents($testFile);
	}
	foreach ($manifest as $className => $entry) {
		$nativeClass = 'PHPStanTurbo\\' . basename($entry['cpp'], '.cpp');
		if (str_contains($tests, $className) || str_contains($tests, $nativeClass)) {
			continue;
		}
		$problems[] = sprintf('%s has no differential coverage — no turbo-ext/tests/*.php script mentions it or %s', $className, $nativeClass);
	}

	return $problems;
}

/**
 * The Unix builds glob their sources (the Makefile wildcard, config.m4's
 * echo), but config.w32 lists them explicitly — a new .cpp missing from that
 * list only surfaces as an unresolved external on the Windows link.
 *
 * @return list<string> problems
 */
function checkWindowsSources(): array
{
	$problems = [];
	preg_match_all('~(\w+)\.cpp~', file_get_contents('turbo-ext/config.w32'), $m);
	$listed = $m[1];
	$actual = array_map(
		static fn ($f) => basename($f, '.cpp'),
		array_merge(glob('turbo-ext/src/*.cpp'), glob('turbo-ext/src/parser/*.cpp')),
	);
	foreach (array_diff($actual, $listed) as $missing) {
		$problems[] = sprintf('%s.cpp is missing from the source lists in turbo-ext/config.w32 — the Windows build would fail with an unresolved external at link time', $missing);
	}
	foreach (array_diff($listed, $actual) as $extra) {
		$problems[] = sprintf('turbo-ext/config.w32 mentions %s.cpp, which does not exist under turbo-ext/src/', $extra);
	}

	return $problems;
}

$failed = false;
foreach (array_merge(checkStructure($manifest), checkClassMap($referenced), checkSmokeCoverage($manifest), checkWindowsSources()) as $problem) {
	printf("✗ %s\n", $problem);
	$failed = true;
}
foreach ($manifest as $className => $entry) {
	$result = analyzePair($className, $entry);
	if ($result['skipped'] !== null) {
		printf("~ %s: skipped, %s\n", $className, $result['skipped']);
		continue;
	}
	if ($result['missingNative'] === [] && $result['orphanNative'] === []) {
		printf("✓ %s: %d methods paired\n", $className, count($result['cppMethods']));
		continue;
	}
	$failed = true;
	foreach ($result['missingNative'] as $name) {
		printf("✗ %s::%s() is public in %s but has no PHP_METHOD in %s\n", $className, $name, $entry['php'], $entry['cpp']);
	}
	foreach ($result['orphanNative'] as $name) {
		printf("✗ PHP_METHOD %s in %s has no counterpart method in %s\n", $name, $entry['cpp'], $entry['php']);
	}
}
exit($failed ? 1 : 0);
