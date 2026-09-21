<?php declare(strict_types = 1);

/**
 * Corpus-differential test for the native php-parser engine: parses every
 * .php file in the corpus with both PHPStanTurbo\ParserRunner (native) and
 * $parser->parse() (PHP), and requires byte-identical serialized ASTs,
 * identical collected errors, and identical token counts.
 *
 * Run with the extension loaded and vendor/ installed:
 *   php -d extension=$PWD/turbo-ext/phpstan_turbo.so turbo-ext/tests/parser-corpus.php [maxFiles]
 *
 * The enabler is NOT run; the native class is declared as PHPStanTurbo\ParserRunner
 * (tests/activate-prefixed.php) and called directly.
 */

$root = dirname(__DIR__, 2);
chdir($root);

if (!extension_loaded('phpstan_turbo')) {
	fwrite(STDERR, "the phpstan_turbo extension is not loaded\n");
	exit(1);
}

require __DIR__ . '/activate-prefixed.php';
require __DIR__ . '/parser-compare.php';

$maxFiles = isset($argv[1]) ? (int) $argv[1] : PHP_INT_MAX;

$dirs = [
	'src',
	'tests/PHPStan',
	'vendor/nikic/php-parser/lib',
	'vendor/phpstan/phpdoc-parser/src',
	'vendor/symfony',
	'stubs',
	// regression fixtures for behavior only malformed/exotic input exercises
	// (dropped T_BAD_CHARACTER, aborting escape-sequence errors, the
	// first-class-callable exit() construction-plan poisoning)
	'turbo-ext/tests/parser-fixtures',
];

$files = [];
foreach ($dirs as $dir) {
	if (!is_dir($dir)) {
		continue;
	}
	$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
	foreach ($it as $file) {
		if ($file->getExtension() === 'php') {
			$files[] = $file->getPathname();
		}
	}
}
sort($files);
$files = array_slice($files, 0, $maxFiles);

$lexer = new PhpParser\Lexer();
$phpVersion = PhpParser\PhpVersion::fromString('8.5');
$parserForNative = new PhpParser\Parser\Php8($lexer, $phpVersion);
$parserForPhp = new PhpParser\Parser\Php8($lexer, $phpVersion);

$checked = 0;
$failed = 0;
$parseErrors = 0;
$firstDiffs = [];

foreach ($files as $file) {
	$code = file_get_contents($file);
	if ($code === false) {
		continue;
	}

	[$problems, $hadParseErrors] = compareParse($code, $parserForNative, $parserForPhp);

	$checked++;
	if ($hadParseErrors) {
		$parseErrors++;
	}
	if ($problems !== []) {
		$failed++;
		if (count($firstDiffs) < 10) {
			$firstDiffs[] = sprintf("=== %s ===\n%s", $file, implode("\n", $problems));
		}
	}
}

// The corpus above runs the plain lexer; these snippets run the emulative
// lexer, which on hosts older than the targeted version polyfills missing
// tokens with ids assigned from -1 downward (php-parser's
// defineCompatibilityTokens()) — negative token ids then appear in the actual
// token stream, not just among phpTokenToSymbol's keys. On the newest host the
// same snippets remain a plain differential over natively-tokenized syntax.
$emulativeSnippets = [
	'<?php $r = "abc" |> strlen(...);',
	'<?php $r = "abc" |> (fn ($s) => $s . "!") |> strtoupper(...);',
	'<?php (void) foo();',
	'<?php class C { public private(set) int $x = 1; }',
];
$emulativeParserForNative = new PhpParser\Parser\Php8(new PhpParser\Lexer\Emulative($phpVersion), $phpVersion);
$emulativeParserForPhp = new PhpParser\Parser\Php8(new PhpParser\Lexer\Emulative($phpVersion), $phpVersion);

foreach ($emulativeSnippets as $snippet) {
	[$problems, $hadParseErrors] = compareParse($snippet, $emulativeParserForNative, $emulativeParserForPhp);

	$checked++;
	if ($hadParseErrors) {
		$parseErrors++;
	}
	if ($problems !== []) {
		$failed++;
		if (count($firstDiffs) < 10) {
			$firstDiffs[] = sprintf("=== emulative: %s ===\n%s", $snippet, implode("\n", $problems));
		}
	}
}

foreach ($firstDiffs as $diff) {
	echo $diff, "\n\n";
}
printf("corpus: %d files checked, %d with parse errors (identical both sides counts as pass), %d FAILED\n", $checked, $parseErrors, $failed);
exit($failed > 0 ? 1 : 0);
