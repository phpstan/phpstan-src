<?php declare(strict_types = 1);

/**
 * Verifies the shadowed PHP classes and their native C++ implementations are
 * in sync, pairing them by method name, driven by the
 * ShadowedByTurboExtension attributes (used by CI): every shadowed pair's
 * files exist, every public method of the PHP class has a PHP_METHOD
 * counterpart in the C++ file, and every PHP_METHOD corresponds to a method
 * of the PHP class. Methods a PHP class gets from the traits it uses count
 * as its own (resolved recursively, with `as`/`insteadof` adaptations
 * applied the way PHP applies them), and so do the methods a C++ class gets
 * from the shared trait registrars in turbo-ext/src/TypeTraits.cpp it runs.
 * The two generated vendor/turbo-* files are re-derived through the shared
 * TurboAttributeCollector and byte-compared, so a stale autoloader dump
 * fails here.
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

		if (($id === T_CLASS || $id === T_TRAIT) && $prevSignificant !== T_DOUBLE_COLON && $prevSignificant !== T_NEW && !$inClass) {
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
			if ($prevSignificant === T_FUNCTION && $id !== null && $text !== '(') {
				// the method name — not necessarily T_STRING: names like
				// and()/or()/static() tokenize as T_LOGICAL_AND/T_LOGICAL_OR/
				// T_STATIC, so this comes before the modifier branches
				$pendingMethod = [$text, $visibility, $static, $declStartLine ?? $line];
			} elseif ($id === T_PUBLIC) {
				$visibility = 'public';
			} elseif ($id === T_PROTECTED) {
				$visibility = 'protected';
			} elseif ($id === T_PRIVATE) {
				$visibility = 'private';
			} elseif ($id === T_STATIC) {
				$static = true;
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
 * Resolves a class-like name the way the file's namespace and imports
 * resolve it.
 *
 * @param array<string, string> $imports lowercase alias => FQCN
 */
function resolvePhpName(string $name, string $namespace, array $imports): string
{
	if (str_starts_with($name, '\\')) {
		return substr($name, 1);
	}
	$segments = explode('\\', $name);
	$first = strtolower($segments[0]);
	if (isset($imports[$first])) {
		$segments[0] = $imports[$first];
		return implode('\\', $segments);
	}

	return ($namespace === '' ? '' : $namespace . '\\') . $name;
}

/**
 * The source file of a class-like under src/ (PSR-4 for the PHPStan
 * namespace), null for anything else (vendored traits are not paired).
 */
function phpClassFile(string $className): ?string
{
	if (!str_starts_with($className, 'PHPStan\\')) {
		return null;
	}
	$file = 'src/' . str_replace('\\', '/', substr($className, strlen('PHPStan\\'))) . '.php';

	return is_file($file) ? $file : null;
}

/**
 * The `use Trait;` declarations inside the first class body of the file,
 * with their adaptation rules; names resolved through the file's namespace
 * and imports.
 *
 * @return list<array{traits: list<string>, rules: list<array{kind: 'as'|'insteadof', trait: string|null, method: string, alias: string|null, visibility: string|null, excluded: list<string>}>}>
 */
function parsePhpTraitUses(string $file): array
{
	$tokens = token_get_all(file_get_contents($file));
	$count = count($tokens);
	$namespace = '';
	$imports = [];
	$uses = [];

	$depth = 0;
	$awaitingClassBrace = false;
	$inClass = false;
	$classDepth = 0;
	$prevSignificant = null;

	// the significant token texts from $from up to (not including) the first
	// of $terminators at the current nesting; returns [texts, index of the terminator]
	$collect = static function (int $from, array $terminators) use ($tokens, $count): array {
		$texts = [];
		for ($j = $from; $j < $count; $j++) {
			$t = $tokens[$j];
			[$tid, $ttext] = is_array($t) ? [$t[0], $t[1]] : [null, $t];
			if ($tid === T_WHITESPACE || $tid === T_COMMENT || $tid === T_DOC_COMMENT) {
				continue;
			}
			if (in_array($ttext, $terminators, true)) {
				return [$texts, $j];
			}
			$texts[] = $ttext;
		}

		return [$texts, $count];
	};

	// "A :: foo" pieces => "A::foo", split on commas
	$splitList = static function (array $texts): array {
		$items = [];
		$current = '';
		foreach ($texts as $text) {
			if ($text === ',') {
				$items[] = $current;
				$current = '';
				continue;
			}
			$current .= $text;
		}
		if ($current !== '') {
			$items[] = $current;
		}

		return $items;
	};

	for ($i = 0; $i < $count; $i++) {
		$token = $tokens[$i];
		[$id, $text] = is_array($token) ? [$token[0], $token[1]] : [null, $token];
		if ($id === T_WHITESPACE || $id === T_COMMENT || $id === T_DOC_COMMENT) {
			continue;
		}

		if ($id === T_NAMESPACE && $prevSignificant !== T_DOUBLE_COLON && !$inClass && $depth === 0) {
			[$texts, $end] = $collect($i + 1, [';', '{']);
			$namespace = implode('', $texts);
			$i = $end;
			$prevSignificant = $text;
			continue;
		}

		if ($id === T_USE && !$inClass && $depth === 0) {
			[$texts, $end] = $collect($i + 1, [';']);
			$i = $end;
			if ($texts !== [] && (strtolower($texts[0]) === 'function' || strtolower($texts[0]) === 'const')) {
				continue; // no class-likes here
			}
			foreach ($splitList($texts) as $import) {
				$parts = preg_split('~\s+as\s+~i', $import);
				$fqcn = ltrim($parts[0], '\\');
				$alias = $parts[1] ?? substr($fqcn, (int) strrpos('\\' . $fqcn, '\\'));
				$imports[strtolower($alias)] = $fqcn;
			}
			continue;
		}

		if (($id === T_CLASS || $id === T_TRAIT) && $prevSignificant !== T_DOUBLE_COLON && $prevSignificant !== T_NEW && !$inClass) {
			$awaitingClassBrace = true;
		} elseif ($text === '{' || $id === T_CURLY_OPEN || $id === T_DOLLAR_OPEN_CURLY_BRACES) {
			$depth++;
			if ($awaitingClassBrace) {
				$inClass = true;
				$classDepth = $depth;
				$awaitingClassBrace = false;
			}
		} elseif ($text === '}') {
			$depth--;
			if ($inClass && $depth < $classDepth) {
				break; // first class only
			}
		} elseif ($id === T_USE && $inClass && $depth === $classDepth) {
			[$texts, $end] = $collect($i + 1, [';', '{']);
			$traits = [];
			foreach ($splitList($texts) as $traitName) {
				$traits[] = resolvePhpName($traitName, $namespace, $imports);
			}
			$rules = [];
			if ($end < $count && $tokens[$end] === '{') {
				$j = $end + 1;
				while ($j < $count && $tokens[$j] !== '}') {
					[$ruleTexts, $j] = $collect($j, [';', '}']);
					if ($ruleTexts === []) {
						if ($j < $count && $tokens[$j] === ';') {
							$j++;
						}
						continue;
					}
					$insteadof = array_search('insteadof', array_map('strtolower', $ruleTexts), true);
					$as = array_search('as', array_map('strtolower', $ruleTexts), true);
					$subject = implode('', array_slice($ruleTexts, 0, $insteadof !== false ? $insteadof : $as));
					$subjectTrait = null;
					$method = $subject;
					if (str_contains($subject, '::')) {
						[$subjectTrait, $method] = explode('::', $subject, 2);
						$subjectTrait = resolvePhpName($subjectTrait, $namespace, $imports);
					}
					if ($insteadof !== false) {
						$rules[] = [
							'kind' => 'insteadof',
							'trait' => $subjectTrait,
							'method' => $method,
							'alias' => null,
							'visibility' => null,
							'excluded' => array_map(
								static fn (string $name): string => resolvePhpName($name, $namespace, $imports),
								$splitList(array_slice($ruleTexts, $insteadof + 1)),
							),
						];
					} elseif ($as !== false) {
						$visibility = null;
						$alias = null;
						foreach (array_slice($ruleTexts, $as + 1) as $modifier) {
							if (in_array(strtolower($modifier), ['public', 'protected', 'private'], true)) {
								$visibility = strtolower($modifier);
							} else {
								$alias = $modifier;
							}
						}
						$rules[] = ['kind' => 'as', 'trait' => $subjectTrait, 'method' => $method, 'alias' => $alias, 'visibility' => $visibility, 'excluded' => []];
					}
					if ($j < $count && $tokens[$j] === ';') {
						$j++;
					}
				}
				$end = $j;
			}
			$uses[] = ['traits' => $traits, 'rules' => $rules];
			$i = $end;
			$prevSignificant = ';';
			continue;
		}

		$prevSignificant = $id !== null ? ($text === '&' ? $prevSignificant : $id) : $text;
	}

	return $uses;
}

/**
 * The class's methods including the ones it gets from the traits it uses,
 * recursively, with the adaptation rules applied the way PHP applies them:
 * the class body wins over a trait, `insteadof` excludes a trait's method,
 * `as` adds an alias (the original stays) or changes the visibility.
 *
 * @return array<string, array{visibility: string, static: bool, startLine: int, endLine: int}>
 */
function parsePhpMethodsWithTraits(string $file): array
{
	$methods = parsePhpMethods($file);
	foreach (parsePhpTraitUses($file) as $use) {
		$excluded = [];
		foreach ($use['rules'] as $rule) {
			if ($rule['kind'] !== 'insteadof') {
				continue;
			}
			foreach ($rule['excluded'] as $excludedTrait) {
				$excluded[strtolower($excludedTrait)][$rule['method']] = true;
			}
		}
		$fromTraits = [];
		foreach ($use['traits'] as $trait) {
			$traitFile = phpClassFile($trait);
			if ($traitFile === null) {
				continue;
			}
			$traitMethods = parsePhpMethodsWithTraits($traitFile);
			foreach ($traitMethods as $name => $info) {
				if (isset($excluded[strtolower($trait)][$name])) {
					continue;
				}
				$fromTraits[$name] ??= $info;
			}
			foreach ($use['rules'] as $rule) {
				if ($rule['kind'] !== 'as' || !isset($traitMethods[$rule['method']])) {
					continue;
				}
				if ($rule['trait'] !== null && strtolower($rule['trait']) !== strtolower($trait)) {
					continue;
				}
				$info = $traitMethods[$rule['method']];
				if ($rule['visibility'] !== null) {
					$info['visibility'] = $rule['visibility'];
				}
				$fromTraits[$rule['alias'] ?? $rule['method']] = $info;
			}
		}
		foreach ($fromTraits as $name => $info) {
			$methods[$name] ??= $info;
		}
	}

	return $methods;
}

/**
 * The registrars the generated ptdecl::<Stem>::registerTraits() runs, for a
 * class that wires its traits through the generated header instead of
 * calling each pt_type_trait_*() itself.
 *
 * @return list<string>
 */
function generatedTraitRegistrars(string $stem): array
{
	$file = 'turbo-ext/src/generated/' . $stem . '.h';
	if (!is_file($file)) {
		throw new RuntimeException(sprintf('%s does not exist', $file));
	}
	preg_match_all('/^\tpt_type_trait_(\w+)\(cls\);$/m', (string) file_get_contents($file), $m);

	return $m[1];
}

/**
 * The traitMethod() declarations of one pt_type_trait_*() registrar in
 * turbo-ext/src/TypeTraits.cpp — what a class running it gets.
 *
 * @return array<string, array{startLine: int, endLine: int}>
 */
function parseCppTraitRegistrar(string $trait): array
{
	static $cache = [];
	if (isset($cache[$trait])) {
		return $cache[$trait];
	}
	$file = 'turbo-ext/src/TypeTraits.cpp';
	$lines = file($file);
	$start = null;
	foreach ($lines as $i => $lineText) {
		if (preg_match('/^void pt_type_trait_' . preg_quote($trait, '/') . '\(reg::Class &cls\)/', $lineText) === 1) {
			$start = $i;
			break;
		}
	}
	if ($start === null) {
		throw new RuntimeException(sprintf('%s declares no pt_type_trait_%s() registrar', $file, $trait));
	}
	$methods = [];
	$signatures = [];
	for ($j = $start + 1; $j < count($lines); $j++) {
		if (rtrim($lines[$j]) === '}') {
			break;
		}
		if (preg_match('/^\s*namespace sigs = ptdecl::(\w+)::sig;/', $lines[$j], $alias) === 1) {
			$signatures = generatedSignatureNames($alias[1]);
		}
		if (preg_match('/^\s*cls\.traitMethod\((?:"(\w+)"|sigs::(\w+))/', $lines[$j], $m) === 1) {
			$name = ($m[1] ?? '') !== '' ? $m[1] : ($signatures[$m[2]] ?? $m[2]);
			$methods[$name] = ['startLine' => $j + 1, 'endLine' => $j + 1];
		}
	}

	return $cache[$trait] = $methods;
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
			&& preg_match('/^\s*(?:cls\.|\.)(?:method|traitMethod)(?:<[^(]*>)?\("(\w+)"/', $lineText, $m) !== 1
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

	// the shared trait registrars the class runs (TypeTraits.cpp): what
	// they declare is the class's too, behind the class's own methods —
	// reg::Class::traitMethod() skips names the class declared itself
	foreach ($lines as $lineText) {
		if (preg_match('/^\s*pt_type_trait_(\w+)\(cls\);/', $lineText, $m) === 1) {
			$registrars = [$m[1]];
		} elseif (preg_match('/^\s*ptdecl::(\w+)::registerTraits\(cls\);/', $lineText, $m) === 1) {
			$registrars = generatedTraitRegistrars($m[1]);
		} else {
			continue;
		}
		foreach ($registrars as $registrar) {
			foreach (parseCppTraitRegistrar($registrar) as $name => $info) {
				$methods[$name] ??= $info;
			}
		}
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

	$phpMethods = parsePhpMethodsWithTraits($entry['php']);
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
		$source = file_get_contents($file);
		preg_match_all('~reg::Class\s+\w+\("((?:PHPStan|PhpParser)\\\\[^"]+)"\)~', $source, $m);
		if ($m[1] === []) {
			continue;
		}
		// A port awaiting its flip declares its class differential-only
		// (reg::Class::shadowDifferentialOnly()): the prefixed harness
		// compares it against the twin while the twin stays the live class,
		// so no attribute names it yet — parity is checked once it lands.
		if (str_contains($source, 'shadowDifferentialOnly(')) {
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
