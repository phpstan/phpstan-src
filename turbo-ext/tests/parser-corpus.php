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
 * The enabler is NOT run; PHPStanTurbo\ParserRunner is called directly.
 */

$root = dirname(__DIR__, 2);
chdir($root);

if (!extension_loaded('phpstan_turbo')) {
	fwrite(STDERR, "the phpstan_turbo extension is not loaded\n");
	exit(1);
}

require $root . '/vendor/autoload.php';

$maxFiles = isset($argv[1]) ? (int) $argv[1] : PHP_INT_MAX;

$dirs = [
	'src',
	'tests/PHPStan',
	'vendor/nikic/php-parser/lib',
	'vendor/phpstan/phpdoc-parser/src',
	'vendor/symfony',
	'stubs',
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

function summarizeErrors(PhpParser\ErrorHandler\Collecting $handler): string
{
	$out = [];
	foreach ($handler->getErrors() as $error) {
		$out[] = $error->getRawMessage() . '|' . var_export($error->getAttributes(), true);
	}

	return implode("\n", $out);
}

$checked = 0;
$failed = 0;
$parseErrors = 0;
$firstDiffs = [];

foreach ($files as $file) {
	$code = file_get_contents($file);
	if ($code === false) {
		continue;
	}

	$nativeHandler = new PhpParser\ErrorHandler\Collecting();
	$phpHandler = new PhpParser\ErrorHandler\Collecting();

	$nativeThrew = null;
	$phpThrew = null;
	$nativeAst = null;
	$phpAst = null;
	try {
		$nativeAst = PHPStanTurbo\ParserRunner::parse($parserForNative, $code, $nativeHandler);
	} catch (Throwable $e) {
		$nativeThrew = get_class($e) . ': ' . $e->getMessage();
	}
	$nativeTokens = count($parserForNative->getTokens());
	try {
		$phpAst = $parserForPhp->parse($code, $phpHandler);
	} catch (Throwable $e) {
		$phpThrew = get_class($e) . ': ' . $e->getMessage();
	}
	$phpTokens = count($parserForPhp->getTokens());

	$checked++;
	$problems = [];
	if ($nativeThrew !== $phpThrew) {
		$problems[] = sprintf('throw mismatch: native=%s php=%s', $nativeThrew ?? '-', $phpThrew ?? '-');
	}
	if ($nativeTokens !== $phpTokens) {
		$problems[] = sprintf('token count mismatch: native=%d php=%d', $nativeTokens, $phpTokens);
	}
	$nativeErrors = summarizeErrors($nativeHandler);
	$phpErrors = summarizeErrors($phpHandler);
	if ($nativeErrors !== $phpErrors) {
		$problems[] = sprintf("errors mismatch:\n--- native ---\n%s\n--- php ---\n%s", $nativeErrors, $phpErrors);
	}
	if ($phpErrors !== '') {
		$parseErrors++;
	}
	if ($problems === []) {
		$nativeSer = $nativeAst === null ? 'NULL' : serialize($nativeAst);
		$phpSer = $phpAst === null ? 'NULL' : serialize($phpAst);
		if ($nativeSer !== $phpSer) {
			// find the first differing offset for the report
			$len = min(strlen($nativeSer), strlen($phpSer));
			$at = 0;
			while ($at < $len && $nativeSer[$at] === $phpSer[$at]) {
				$at++;
			}
			$problems[] = sprintf(
				"AST mismatch at byte %d:\n  native: …%s…\n  php:    …%s…",
				$at,
				substr($nativeSer, max(0, $at - 60), 160),
				substr($phpSer, max(0, $at - 60), 160),
			);
		}
	}

	if ($problems !== []) {
		$failed++;
		if (count($firstDiffs) < 10) {
			$firstDiffs[] = sprintf("=== %s ===\n%s", $file, implode("\n", $problems));
		}
	}
}

foreach ($firstDiffs as $diff) {
	echo $diff, "\n\n";
}
printf("corpus: %d files checked, %d with parse errors (identical both sides counts as pass), %d FAILED\n", $checked, $parseErrors, $failed);
exit($failed > 0 ? 1 : 0);
