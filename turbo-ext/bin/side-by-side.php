<?php declare(strict_types = 1);

/**
 * Pairs the shadowed PHP classes with their native C++ implementations by
 * method name, driven by turbo-ext/shadowed-classes.json.
 *
 * Usage:
 *   php turbo-ext/bin/side-by-side.php --check
 *       Verify the two implementations are in sync (used by CI):
 *       every manifest file exists, every public method of the PHP class
 *       has a PHP_METHOD counterpart in the C++ file, and every PHP_METHOD
 *       corresponds to a method of the PHP class. Vendored entries are
 *       skipped when vendor/ is not installed.
 *
 *   php turbo-ext/bin/side-by-side.php [output.html]
 *       Render a side-by-side HTML view of each method pair
 *       (default output: turbo-ext/side-by-side.html).
 *
 * No dependencies — runs on any PHP >= 8.0 without vendor/.
 */

error_reporting(E_ALL);

$root = dirname(__DIR__, 2);
chdir($root);

$manifestFile = 'turbo-ext/shadowed-classes.json';
$manifest = json_decode(file_get_contents($manifestFile), true, 8, JSON_THROW_ON_ERROR);

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
 * The manifest must stay complete: every stub, every enabler require_once and
 * every per-class .cpp file must correspond to a manifest entry (and vice
 * versa), and stubs must be empty shells — a shadowed class missing from the
 * manifest would silently escape the parity and version-coupling checks.
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

	$sets = [
		'turbo-ext/stubs/*.php' => array_map(static fn ($f) => basename($f, '.php'), glob('turbo-ext/stubs/*.php')),
		'require_once in TurboExtensionEnabler' => (static function (): array {
			preg_match_all('~stubs/(\w+)\.php~', file_get_contents('src/Turbo/TurboExtensionEnabler.php'), $m);
			return $m[1];
		})(),
		// main.cpp hosts extension-only classes (Runtime) that shadow no PHP
		// implementation — they never get a manifest entry.
		'class-defining .cpp files (PHP_METHOD or reg::Class)' => array_values(array_diff(array_filter(array_map(
			static fn ($f) => preg_match('~PHP_METHOD\(PHPStanTurbo_|reg::Class\s+\w+\("PHPStanTurbo~', file_get_contents($f)) === 1 ? basename($f, '.cpp') : null,
			array_merge(glob('turbo-ext/src/*.cpp'), glob('turbo-ext/src/parser/*.cpp')),
		)), ['main'])),
	];
	foreach ($sets as $what => $names) {
		foreach (array_diff($names, array_keys($fromManifest)) as $extra) {
			$problems[] = sprintf('%s from %s has no entry in shadowed-classes.json', $extra, $what);
		}
		foreach (array_diff(array_keys($fromManifest), $names) as $missing) {
			$problems[] = sprintf('manifest entry %s (%s) is missing from %s', $fromManifest[$missing], $missing, $what);
		}
	}

	// stubs must be empty final shells extending the matching native class
	foreach (glob('turbo-ext/stubs/*.php') as $stubFile) {
		$base = basename($stubFile, '.php');
		$src = file_get_contents($stubFile);
		if (preg_match('~class\s+(\w+)\s+extends\s+\\\\PHPStanTurbo\\\\(\w+)\s*\{(.*)\}~s', $src, $m) !== 1) {
			$problems[] = sprintf('%s does not declare "class X extends \PHPStanTurbo\X"', $stubFile);
			continue;
		}
		if ($m[1] !== $base || $m[2] !== $base) {
			$problems[] = sprintf('%s: class %s extends \PHPStanTurbo\%s — names must both match the file name', $stubFile, $m[1], $m[2]);
		}
		if (trim($m[3]) !== '') {
			$problems[] = sprintf('%s: the stub body must be empty — a member declared here would exist only when the extension is loaded', $stubFile);
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

if (in_array('--update-manifest', $argv, true)) {
	// Regenerate the manifest from ground truth: each stub names the shadowed
	// class, the autoloader locates its PHP implementation (the enabler is NOT
	// run, so class names resolve to the originals, not the stubs), and the
	// native file is the same-named .cpp. Run this after adding a shadowed
	// class; the CI checks then verify the committed manifest matches reality.
	require $root . '/vendor/autoload.php';
	$entries = [];
	foreach (glob('turbo-ext/stubs/*.php') as $stubFile) {
		$src = file_get_contents($stubFile);
		if (preg_match('~^namespace\s+([\w\\\\]+);~m', $src, $ns) !== 1
			|| preg_match('~class\s+(\w+)\s+extends\s+\\\\PHPStanTurbo\\\\\w+~', $src, $cls) !== 1
		) {
			fwrite(STDERR, sprintf("cannot parse %s\n", $stubFile));
			exit(1);
		}
		$className = $ns[1] . '\\' . $cls[1];
		$phpFile = substr(realpath((new ReflectionClass($className))->getFileName()), strlen(realpath($root)) + 1);
		$base = basename($stubFile, '.php');
		$cppFile = 'turbo-ext/src/' . $base . '.cpp';
		if (!is_file($cppFile)) {
			$cppFile = 'turbo-ext/src/parser/' . $base . '.cpp';
		}
		$entry = [
			'php' => $phpFile,
			'cpp' => $cppFile,
		];
		if (str_starts_with($phpFile, 'vendor/')) {
			$entry['vendored'] = true;
		}
		$entries[$className] = $entry;
	}
	ksort($entries);
	file_put_contents($manifestFile, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
	printf("wrote %s (%d classes)\n", $manifestFile, count($entries));
	exit(0);
}

$check = in_array('--check', $argv, true);

if ($check) {
	$failed = false;
	foreach (array_merge(checkStructure($manifest), checkWindowsSources()) as $problem) {
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
}

$output = $argv[1] ?? 'turbo-ext/side-by-side.html';

function codePane(string $file, int $startLine, int $endLine): string
{
	$lines = array_slice(file($file), $startLine - 1, $endLine - $startLine + 1);
	$html = '';
	foreach ($lines as $i => $text) {
		$html .= sprintf("<span class=\"ln\">%4d</span>%s\n", $startLine + $i, htmlspecialchars(rtrim($text, "\n"), ENT_QUOTES));
	}

	return sprintf('<pre>%s</pre>', $html);
}

$gitHead = trim((string) shell_exec('git log -1 --format=%h 2>/dev/null'));
$body = '';
$toc = '';

foreach ($manifest as $className => $entry) {
	$result = analyzePair($className, $entry);
	$anchor = strtolower(str_replace('\\', '-', $className));
	$toc .= sprintf('<li><a href="#%s">%s</a></li>', $anchor, htmlspecialchars($className));

	$body .= sprintf(
		'<section id="%s"><h2>%s</h2><p class="files"><span class="php-badge">PHP</span> %s &nbsp; <span class="cpp-badge">C++</span> %s%s</p>',
		$anchor,
		htmlspecialchars($className),
		htmlspecialchars($entry['php']),
		htmlspecialchars($entry['cpp']),
		($entry['vendored'] ?? false) ? ' <em>(PHP side is vendored — pinned by composer.lock)</em>' : '',
	);

	if ($result['skipped'] !== null) {
		$body .= sprintf('<p class="note">Skipped: %s</p></section>', htmlspecialchars($result['skipped']));
		continue;
	}

	foreach ($result['phpMethods'] as $name => $info) {
		$native = $result['cppMethods'][$name] ?? null;
		$label = sprintf(
			'%s%s%s()',
			$info['visibility'] === 'public' ? '' : $info['visibility'] . ' ',
			$info['static'] ? 'static ' : '',
			$name,
		);
		if ($native === null) {
			if ($info['visibility'] === 'public') {
				$body .= sprintf('<h3>%s <span class="warn">no native counterpart!</span></h3>', htmlspecialchars($label));
				$body .= sprintf('<div class="pair"><div>%s</div><div class="note">missing</div></div>', codePane($entry['php'], $info['startLine'], $info['endLine']));
			} else {
				$body .= sprintf('<h3>%s <span class="phponly">PHP-only helper (native side inlines or reimplements it)</span></h3>', htmlspecialchars($label));
			}
			continue;
		}
		$body .= sprintf('<h3>%s</h3><div class="pair"><div>%s</div><div>%s</div></div>', htmlspecialchars($label), codePane($entry['php'], $info['startLine'], $info['endLine']), codePane($entry['cpp'], $native['startLine'], $native['endLine']));
	}

	foreach ($result['orphanNative'] as $name) {
		$native = $result['cppMethods'][$name];
		$body .= sprintf('<h3>%s() <span class="warn">native only — no PHP counterpart!</span></h3>', htmlspecialchars($name));
		$body .= sprintf('<div class="pair"><div class="note">missing</div><div>%s</div></div>', codePane($entry['cpp'], $native['startLine'], $native['endLine']));
	}

	$body .= '</section>';
}

$html = <<<HTML
<meta charset="utf-8">
<title>phpstan_turbo — PHP ↔ C++ side by side</title>
<style>
:root { color-scheme: light dark; --border: #8884; --accent: #4a7dbd; }
body { font-family: system-ui, sans-serif; margin: 1.5rem; max-width: 1800px; }
h2 { border-bottom: 2px solid var(--accent); padding-bottom: .3rem; margin-top: 2.5rem; }
h3 { font-family: ui-monospace, monospace; margin: 1.5rem 0 .4rem; }
.pair { display: grid; grid-template-columns: 1fr 1fr; gap: .8rem; align-items: start; }
pre { border: 1px solid var(--border); border-radius: 6px; padding: .6rem; overflow-x: auto; font-size: .78rem; line-height: 1.45; margin: 0; }
.ln { display: inline-block; width: 3.2em; opacity: .45; user-select: none; }
.php-badge, .cpp-badge { font-size: .7rem; font-weight: 700; padding: .1rem .4rem; border-radius: 4px; color: #fff; }
.php-badge { background: #7377ad; } .cpp-badge { background: #649ad1; }
.files { opacity: .8; }
.warn { color: #c33; font-family: system-ui; font-size: .85rem; font-weight: 600; }
.phponly { color: #888; font-family: system-ui; font-size: .85rem; font-weight: 400; }
.note { opacity: .6; font-style: italic; padding: .6rem; }
.toc li { margin: .15rem 0; }
@media (max-width: 1000px) { .pair { grid-template-columns: 1fr; } }
</style>
<h1>phpstan_turbo — shadowed classes, PHP ↔ C++</h1>
<p>Generated from <code>turbo-ext/shadowed-classes.json</code> at commit <code>{$gitHead}</code>
by <code>php turbo-ext/bin/side-by-side.php</code>. Left: the PHP implementation
(used when the extension is not loaded). Right: the native implementation the
stub shadows it with.</p>
<ul class="toc">{$toc}</ul>
{$body}
HTML;

file_put_contents($output, $html);
printf("wrote %s (%d classes)\n", $output, count($manifest));
