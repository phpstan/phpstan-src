<?php declare(strict_types = 1);

/**
 * Verifies the shadowed PHP classes and their native C++ implementations are
 * in sync, pairing them by method name, driven by the
 * ShadowedByTurboExtension attributes (used by CI): every shadowed pair's
 * files exist, every public method of the PHP class has a PHP_METHOD
 * counterpart in the C++ file, and every PHP_METHOD corresponds to a method
 * of the PHP class. The two generated vendor/turbo-* files are re-derived
 * through the shared TurboAttributeCollector and byte-compared, so a stale
 * autoloader dump fails here.
 *
 * Usage: php turbo-ext/bin/side-by-side.php
 *
 * Requires vendor/ (run composer install first).
 */

error_reporting(E_ALL);

$root = dirname(__DIR__, 2);
chdir($root);

if (!is_file('vendor/autoload.php')) {
	fwrite(STDERR, "vendor/autoload.php does not exist — run composer install first\n");
	exit(1);
}

require 'vendor/autoload.php';
require_once 'build/PHPStan/Build/TurboAttributeCollector.php';

$collector = new PHPStan\Build\TurboAttributeCollector($root);
$collected = $collector->collect();
$manifest = $collected['manifest'];

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
 * The method names behind the sig:: identifiers of a generated header
 * (turbo-ext/src/generated/<Stem>.h): identifier => PHP method name.
 *
 * @return array<string, string>
 */
function generatedSignatureNames(string $stem): array
{
	static $cache = [];
	if (isset($cache[$stem])) {
		return $cache[$stem];
	}
	$header = 'turbo-ext/src/generated/' . $stem . '.h';
	if (!is_file($header)) {
		throw new RuntimeException(sprintf('%s does not exist — run php turbo-ext/bin/generate-declarations.php', $header));
	}
	preg_match_all('~inline constexpr reg::Sig (\w+) = \{ "(\w+)"~', file_get_contents($header), $m, PREG_SET_ORDER);
	$names = [];
	foreach ($m as [, $identifier, $name]) {
		$names[$identifier] = $name;
	}

	return $cache[$stem] = $names;
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

	// registrations by generated signature name their method through the
	// file's `namespace sigs = ptdecl::<Stem>::sig;` alias
	$signatures = preg_match('/^namespace sigs = ptdecl::(\w+)::sig;$/m', file_get_contents($file), $alias) === 1
		? generatedSignatureNames($alias[1])
		: [];

	foreach ($lines as $i => $lineText) {
		if (
			preg_match('/^\s*(?:static\s+)?PHP_METHOD\(\s*\w+\s*,\s*(\w+)\s*\)/', $lineText, $m) !== 1
			&& preg_match('/^\s*(?:cls\.|\.)method\("(\w+)"/', $lineText, $m) !== 1
		) {
			if (preg_match('/^\s*cls\.(?:method|traitMethod)(?:<[^(]*>)?\(sigs::(\w+)/', $lineText, $sm) !== 1) {
				continue;
			}
			$m = [1 => $signatures[$sm[1]] ?? $sm[1]];
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

/** @return array{phpMethods: array, cppMethods: array, missingNative: list<string>, orphanNative: list<string>} */
function analyzePair(string $className, array $entry): array
{
	if (!is_file($entry['php'])) {
		throw new RuntimeException(sprintf('%s: PHP file %s does not exist', $className, $entry['php']));
	}
	if (!is_file($entry['cpp'])) {
		throw new RuntimeException(sprintf('%s: C++ file %s does not exist', $className, $entry['cpp']));
	}

	$phpMethods = parsePhpMethods($entry['php']);
	$cppMethods = parseCppMethods($entry['cpp']);

	// Every public PHP method must exist natively — the native class replaces
	// the twin whole, so a missing native method is a fatal when the
	// extension is on.
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

	return ['phpMethods' => $phpMethods, 'cppMethods' => $cppMethods, 'missingNative' => $missingNative, 'orphanNative' => $orphanNative];
}

/**
 * The shadowed pairs must stay complete: every class-defining .cpp file must
 * correspond to a ShadowedByTurboExtension attribute and vice versa — a
 * shadowed class without the attribute would silently escape the parity
 * checks.
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

	// A class-defining .cpp declares its class with reg::Class under the PHP
	// twin's real name (reg::Class::shadow()); extension-only classes with
	// no twin (Runtime, helpers) stay in the PHPStanTurbo namespace and get
	// no manifest entry.
	$cppClasses = [];
	$declaredNames = [];
	foreach (array_merge(glob('turbo-ext/src/*.cpp'), glob('turbo-ext/src/parser/*.cpp')) as $file) {
		preg_match_all('~reg::Class\s+\w+\("((?:PHPStan|PhpParser)\\\\[^"]+)"\)~', file_get_contents($file), $m);
		if ($m[1] === []) {
			continue;
		}
		$cppClasses[] = basename($file, '.cpp');
		foreach ($m[1] as $declared) {
			$declaredNames[basename($file, '.cpp')][] = stripslashes($declared);
		}
	}
	foreach (array_diff($cppClasses, array_keys($fromManifest)) as $extra) {
		$problems[] = sprintf('%s.cpp declares a shadowing class but no ShadowedByTurboExtension attribute names it', $extra);
	}
	foreach (array_diff(array_keys($fromManifest), $cppClasses) as $missing) {
		$problems[] = sprintf('shadowed class %s (%s) declares no reg::Class under its name in the .cpp files', $fromManifest[$missing], $missing);
	}
	foreach ($fromManifest as $cpp => $className) {
		if (isset($declaredNames[$cpp]) && !in_array($className, $declaredNames[$cpp], true)) {
			$problems[] = sprintf('%s.cpp declares [%s], not the shadowed class %s', $cpp, implode(', ', $declaredNames[$cpp]), $className);
		}
	}

	return $problems;
}

/**
 * The generated files must match the attributes they are derived from: what
 * the collector renders now must be byte-identical to what the last
 * autoloader dump wrote.
 *
 * @return list<string> problems
 */
function checkGeneratedArtifacts(PHPStan\Build\TurboAttributeCollector $collector, array $collected): array
{
	$problems = [];
	$expected = [
		'vendor/turbo-shadowed-classes.json' => $collector->renderManifestJson($collected['manifest']),
		'vendor/turbo-class-map.php' => $collector->renderClassMap($collected['classMap']),
	];
	foreach ($expected as $file => $content) {
		if (!is_file($file)) {
			$problems[] = sprintf('%s does not exist — run composer dump-autoload', $file);
			continue;
		}
		if (file_get_contents($file) !== $content) {
			$problems[] = sprintf('%s does not match the attributes — run composer dump-autoload', $file);
		}
	}

	// the declarations generated from the PHP twins
	require_once 'build/PHPStan/Build/TurboDeclarationGenerator.php';
	$generated = (new PHPStan\Build\TurboDeclarationGenerator($collected['manifest']))->render();
	foreach ($generated as $file => $content) {
		if (!is_file($file) || file_get_contents($file) !== $content) {
			$problems[] = sprintf('%s is stale — run php turbo-ext/bin/generate-declarations.php', $file);
		}
	}
	foreach (glob('turbo-ext/src/generated/*.h') ?: [] as $file) {
		if (!isset($generated[$file])) {
			$problems[] = sprintf('%s belongs to no shadowed class — run php turbo-ext/bin/generate-declarations.php', $file);
		}
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

/**
 * Every lowercase identifier the native code passes as PT_LC("...") — the
 * lowercased method names of by-name calls into userland, $this-dispatch and
 * function-table lookups — must name something that exists: a method of a
 * PHP class (trait `as` aliases included), a property or constant, an
 * internal function or member, a type keyword, or a method the extension
 * registers itself. A misspelt name would otherwise only fail when that
 * path first runs.
 *
 * @return list<string> problems
 */
function checkLowercaseNameLiterals(): array
{
	$known = array_fill_keys(['array', 'bool', 'callable', 'false', 'float', 'int', 'iterable', 'mixed', 'never', 'null', 'object', 'resource', 'self', 'static', 'string', 'true', 'void', 'parent'], true);
	$remember = static function (string $name) use (&$known): void {
		$known[strtolower($name)] = true;
	};
	foreach (['src', 'vendor/nikic/php-parser/lib', 'vendor/ondrejmirtes/better-reflection/src', 'vendor/phpstan/phpdoc-parser/src'] as $dir) {
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $file) {
			if ($file->getExtension() !== 'php') {
				continue;
			}
			$code = file_get_contents($file->getPathname());
			preg_match_all('~\bfunction\s+&?\s*(\w+)\s*\(|\bas\s+(?:(?:public|protected|private)\s+)?(\w+)\s*;|\$(\w+)|\bconst\s+(?:\w+\s+)?(\w+)\s*=~', $code, $m);
			foreach ([1, 2, 3, 4] as $group) {
				foreach (array_filter($m[$group]) as $name) {
					$remember($name);
				}
			}
		}
	}
	foreach (array_merge(get_declared_classes(), get_declared_interfaces(), get_declared_traits()) as $className) {
		$class = new ReflectionClass($className);
		if (!$class->isInternal()) {
			continue;
		}
		foreach ($class->getMethods() as $method) {
			$remember($method->getName());
		}
		foreach ($class->getProperties() as $property) {
			$remember($property->getName());
		}
	}
	foreach (get_defined_functions()['internal'] as $function) {
		$remember($function);
	}
	$sources = array_merge(glob('turbo-ext/src/*.cpp'), glob('turbo-ext/src/*.h'), glob('turbo-ext/src/parser/*.cpp'), glob('turbo-ext/src/parser/*.h'));
	foreach ($sources as $source) {
		preg_match_all('~\.(?:method|traitMethod)(?:<[^(]*>)?\("(\w+)"~', file_get_contents($source), $m);
		foreach ($m[1] as $name) {
			$remember($name);
		}
	}

	foreach (['__construct', '__destruct', '__call', '__callstatic', '__get', '__set', '__isset', '__unset', '__sleep', '__wakeup', '__serialize', '__unserialize', '__tostring', '__invoke', '__set_state', '__clone', '__debuginfo'] as $magic) {
		$known[$magic] = true;
	}
	// consumers of string data rather than member names
	$dataConsumers = array_fill_keys(['zend_string_init', 'zend_string_init_interned', 'smart_str_appendl', 'Val::string', 'zv::Val::string'], true);

	$problems = [];
	foreach ($sources as $source) {
		foreach (file($source) as $i => $line) {
			preg_match_all('~PT_LC\("([a-z_][a-z0-9_]*)"\)~', $line, $m, PREG_OFFSET_CAPTURE);
			foreach ($m[1] as [$name, $offset]) {
				if (isset($known[$name])) {
					continue;
				}
				$before = substr($line, 0, $offset - strlen('PT_LC("'));
				if (preg_match('~\{\s*$~', $before) === 1) {
					continue; // an entry of a { PT_LC("..."), ... } lookup table
				}
				if (preg_match('~([\w:]+)\s*\((?:[^()]*,\s*)?$~', $before, $consumer) === 1 && isset($dataConsumers[$consumer[1]])) {
					continue;
				}
				$problems[] = sprintf('%s:%d: PT_LC("%s") names no method, property, constant or function', $source, $i + 1, $name);
			}
		}
	}

	return $problems;
}

$failed = false;
foreach (array_merge(checkStructure($manifest), checkGeneratedArtifacts($collector, $collected), checkWindowsSources(), checkLowercaseNameLiterals()) as $problem) {
	printf("✗ %s\n", $problem);
	$failed = true;
}
foreach ($manifest as $className => $entry) {
	$result = analyzePair($className, $entry);
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
