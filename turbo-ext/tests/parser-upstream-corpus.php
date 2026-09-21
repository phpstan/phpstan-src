<?php declare(strict_types = 1);

/**
 * Differential test for the native php-parser engine over php-parser's own
 * test corpus: every input upstream's test suite parses (test/code/parser,
 * test/code/prettyPrinter, test/code/formatPreservation) goes through the
 * native-vs-PHP comparison of parser-compare.php. Upstream's cases exercise
 * every grammar rule, error recovery and version-gated behavior, including
 * syntax that neither PHPStan's sources nor its test data use yet.
 *
 * The Composer package ships without tests, so the corpus comes from a git
 * checkout of nikic/PHP-Parser at exactly the installed commit. Pass one, or
 * let the test fetch that commit (kept in the system temp directory):
 *   php -d extension=$PWD/turbo-ext/phpstan_turbo.so turbo-ext/tests/parser-upstream-corpus.php [php-parser-checkout]
 *
 * Each case runs under its "!!version=" mode like upstream's tests do, except
 * that versions below 8.0 keep the Php8 grammar (upstream switches to Php7,
 * which the native engine never runs). The enabler is NOT run;
 * PHPStanTurbo\ParserRunner is called directly.
 */

$root = dirname(__DIR__, 2);
chdir($root);

if (!extension_loaded('phpstan_turbo')) {
	fwrite(STDERR, "the phpstan_turbo extension is not loaded\n");
	exit(1);
}

require $root . '/vendor/autoload.php';
require __DIR__ . '/parser-compare.php';

/**
 * @param list<string> $command
 */
function runCommand(array $command): string
{
	$process = proc_open($command, [1 => ['pipe', 'w'], 2 => STDERR], $pipes);
	if ($process === false) {
		fwrite(STDERR, sprintf("cannot run %s\n", implode(' ', $command)));
		exit(1);
	}
	$stdout = stream_get_contents($pipes[1]);
	fclose($pipes[1]);
	$exitCode = proc_close($process);
	if ($exitCode !== 0) {
		fwrite(STDERR, sprintf("%s failed with exit code %d\n", implode(' ', $command), $exitCode));
		exit(1);
	}

	return trim((string) $stdout);
}

/** Upstream's canonicalize() from test/bootstrap.php. */
function canonicalizeTestCode(string $str): string
{
	$str = str_replace("\r\n", "\n", $str);
	$str = rtrim($str, "\n");
	$lines = array_map(static fn (string $line): string => rtrim($line, " \t"), explode("\n", $str));

	return implode("\n", $lines);
}

/**
 * Upstream's CodeTestParser::parseTest(): a name section, then cases of
 * $chunksPerTest sections each, the last of which (the expected output) may
 * start with a "!!key=value,flag" mode line.
 *
 * @return list<array{list<string>, array<string, string|true>}>
 */
function parseTestFile(string $contents, int $chunksPerTest): array
{
	$code = canonicalizeTestCode($contents);
	// @@{expr}@@ embeds characters the file itself cannot hold (e.g. "\0")
	$code = preg_replace_callback('/@@\{(.*?)\}@@/', static fn (array $matches) => eval('return ' . $matches[1] . ';'), $code);
	$sections = preg_split("/\n-----(?:\n|$)/", $code);
	array_shift($sections);

	$cases = [];
	foreach (array_chunk($sections, $chunksPerTest) as $chunk) {
		$modes = [];
		$expected = $chunk[count($chunk) - 1];
		if (strpos($expected, '!!') === 0) {
			$modeLine = substr(explode("\n", $expected, 2)[0], 2);
			foreach (explode(',', $modeLine) as $mode) {
				$keyValue = explode('=', $mode, 2);
				$modes[$keyValue[0]] = $keyValue[1] ?? true;
			}
		}
		$cases[] = [$chunk, $modes];
	}

	return $cases;
}

$installedVersion = Composer\InstalledVersions::getPrettyVersion('nikic/php-parser');
$installedReference = Composer\InstalledVersions::getReference('nikic/php-parser');

if (isset($argv[1])) {
	$checkout = $argv[1];
} else {
	$checkout = sys_get_temp_dir() . '/phpstan-turbo-php-parser-' . $installedReference;
	if (!is_dir($checkout)) {
		// fetch next to the final path and rename, so an interrupted fetch is never reused
		$cloneDir = $checkout . '.' . getmypid();
		runCommand(['git', '-c', 'init.defaultBranch=main', 'init', '--quiet', $cloneDir]);
		runCommand(['git', '-C', $cloneDir, 'fetch', '--quiet', '--depth', '1', 'https://github.com/nikic/PHP-Parser.git', $installedReference]);
		runCommand(['git', '-C', $cloneDir, '-c', 'advice.detachedHead=false', 'checkout', '--quiet', 'FETCH_HEAD']);
		rename($cloneDir, $checkout);
	}
}

$checkoutReference = runCommand(['git', '-C', $checkout, 'rev-parse', 'HEAD']);
if ($checkoutReference !== $installedReference) {
	fwrite(STDERR, sprintf("%s is at %s, but the installed nikic/php-parser %s is %s\n", $checkout, $checkoutReference, $installedVersion, $installedReference));
	exit(1);
}

// what upstream's own tests feed to the parser: [directory, extension, sections per case]
$sets = [
	['test/code/parser', 'test', 2], // CodeParsingTest
	['test/code/prettyPrinter', 'test', 2], // PrettyPrinterTest::testPrettyPrint()
	['test/code/prettyPrinter', 'file-test', 2], // PrettyPrinterTest::testPrettyPrintFile()
	['test/code/formatPreservation', 'test', 3], // PrettyPrinterTest::testFormatPreservingPrint()
];

/** @var array<string, array{PhpParser\Parser\Php8, PhpParser\Parser\Php8}> $parsers */
$parsers = [];
$checked = 0;
$failed = 0;
$parseErrors = 0;
$firstDiffs = [];

foreach ($sets as [$directory, $extension, $chunksPerTest]) {
	$files = [];
	$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($checkout . '/' . $directory, FilesystemIterator::SKIP_DOTS));
	foreach ($it as $file) {
		if ($file->getExtension() === $extension) {
			$files[] = $file->getPathname();
		}
	}
	sort($files);
	if ($files === []) {
		fwrite(STDERR, sprintf("no .%s files in %s/%s\n", $extension, $checkout, $directory));
		exit(1);
	}

	foreach ($files as $file) {
		foreach (parseTestFile((string) file_get_contents($file), $chunksPerTest) as $i => [$sections, $modes]) {
			// PrettyPrinterTest reads parserVersion first, CodeParsingTest only has version
			$version = $modes['parserVersion'] ?? $modes['version'] ?? null;
			$versionKey = is_string($version) ? $version : 'newest';
			if (!isset($parsers[$versionKey])) {
				$phpVersion = is_string($version)
					? PhpParser\PhpVersion::fromString($version)
					: PhpParser\PhpVersion::getNewestSupported();
				// the lexer ParserFactory::createForVersion() picks
				$createParser = static fn (): PhpParser\Parser\Php8 => new PhpParser\Parser\Php8(
					$phpVersion->isHostVersion() ? new PhpParser\Lexer() : new PhpParser\Lexer\Emulative($phpVersion),
					$phpVersion,
				);
				$parsers[$versionKey] = [$createParser(), $createParser()];
			}

			[$problems, $hadParseErrors] = compareParse($sections[0], $parsers[$versionKey][0], $parsers[$versionKey][1]);

			$checked++;
			if ($hadParseErrors) {
				$parseErrors++;
			}
			if ($problems !== []) {
				$failed++;
				if (count($firstDiffs) < 10) {
					$firstDiffs[] = sprintf("=== %s #%d ===\n%s", substr($file, strlen($checkout) + 1), $i, implode("\n", $problems));
				}
			}
		}
	}
}

foreach ($firstDiffs as $diff) {
	echo $diff, "\n\n";
}
printf("upstream corpus (nikic/php-parser %s): %d cases checked, %d with parse errors (identical both sides counts as pass), %d FAILED\n", $installedVersion, $checked, $parseErrors, $failed);
exit($failed > 0 ? 1 : 0);
