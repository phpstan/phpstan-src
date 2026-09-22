<?php declare(strict_types=1);

// Differential test: native PHPStanTurbo\SymbolFinderInFiles vs the PHP twin.
// The native class replaces the twin's whole four-stage pipeline
// (php_strip_whitespace -> prefilter -> PhpFileCleaner -> symbol regex) with
// three native passes, so the bar is identical symbol triples — over every
// PHP file in the repository plus fixtures for the constructs where the
// stages disagree with a naive reading.
//
// Run: php -d extension=.../phpstan_turbo.so turbo-ext/tests/symbol-finder-corpus.php

$root = dirname(__DIR__, 2);
require __DIR__ . '/activate-prefixed.php';

$native = new PHPStanTurbo\SymbolFinderInFiles(new PHPStanTurbo\PhpFileCleaner());

// The references are the twins' own sources with the classes renamed, so they
// cannot drift from the files the port mirrors.
$load = static function (string $file, string $from, string $to) use ($root): void {
	$source = file_get_contents($root . '/src/Reflection/BetterReflection/SourceLocator/' . $file);
	$source = substr($source, strpos($source, 'final class ' . $from));
	eval(str_replace(
		['final class ' . $from, 'PhpFileCleaner $cleaner'],
		['final class ' . $to, 'ReferencePhpFileCleaner $cleaner'],
		$source,
	));
};
$load('PhpFileCleaner.php', 'PhpFileCleaner', 'ReferencePhpFileCleaner');
$load('SymbolFinderInFiles.php', 'SymbolFinderInFiles', 'ReferenceSymbolFinderInFiles');
$reference = new ReferenceSymbolFinderInFiles(new ReferencePhpFileCleaner());

$failures = 0;
$checked = 0;

$compare = static function (array $files, string $label) use ($native, $reference, &$failures, &$checked): void {
	foreach ([true, false] as $supportsEnums) {
		$checked += count($files);
		$a = $native->findSymbols($files, $supportsEnums);
		$b = $reference->findSymbols($files, $supportsEnums);
		if ($a === $b) {
			continue;
		}

		foreach ($files as $file) {
			if (($a[$file] ?? null) === ($b[$file] ?? null)) {
				continue;
			}

			$failures++;
			if ($failures > 10) {
				continue;
			}
			printf("FAIL: %s%s (supportsEnums=%s)\n", $label, $file, $supportsEnums ? 'true' : 'false');
			printf("  native: %s\n", json_encode($a[$file] ?? null));
			printf("  php   : %s\n", json_encode($b[$file] ?? null));
		}
	}
};

// ---- synthetic fixtures ----
$fixtures = [
	'plain class' => "<?php\nnamespace A\\B;\nclass C {}\n",
	'class constant vs global' => "<?php\nnamespace N;\nclass A { const X = 1; }\nconst G = 2;\n",
	'interface const' => "<?php\ninterface I { const X = 1; }\n",
	'enum const' => "<?php\nenum E: string { case A = 'a'; const X = 1; }\n",
	'global constants' => "<?php\nconst A = 1;\nconst B = 2;\n",
	'define' => "<?php\ndefine('FOO', 1);\ndefine(\"N\\\\BAR\", 2);\ndefine('N\\\\\\\\BAZ', 3);\n",
	'define leading slash' => "<?php\n\\define('SLASHED', 1);\n",
	'define with variable' => "<?php\ndefine(\$name, 1);\n\$x = 'function evil(';\n",
	'define string with code' => "<?php\ndefine('FOO class Baz', 1);\n",
	'functions' => "<?php\nfunction a() {}\nfunction &b() {}\nfunction  c  () {}\n\$f = function () {};\n",
	'anonymous class' => "<?php\n\$a = new class extends Foo implements Bar {};\nclass Real {}\n",
	'anonymous class with args' => "<?php\n\$a = new class(1) extends Foo {};\n",
	'namespace braces' => "<?php\nnamespace A { class X {} }\nnamespace B { class Y {} }\n",
	'global namespace braces' => "<?php\nnamespace { class X {} }\n",
	'namespace with spaces' => "<?php\nnamespace A \\ B ;\nclass C {}\n",
	'uppercase keywords' => "<?php\nCLASS Upper {}\nCONST X = 1;\nFUNCTION F() {}\n",
	'comment splits identifier' => "<?php cl/*x*/ass Foo {}\n",
	'comment after keyword' => "<?php class/*x*/Foo {}\n",
	'comment between keyword and name' => "<?php class /*x*/ Foo {}\n",
	'hash comment' => "<?php\n# class Commented {}\nclass Real {}\n",
	'attribute' => "<?php\n#[Attr(name: 'class Fake')]\nclass Real {}\n",
	'line comment ends at close tag' => "<?php // comment ?>\n<?php class Real {} ?>\n",
	'heredoc' => "<?php\n\$a = <<<EOT\nclass NotAClass {}\nEOT;\nclass Real {}\n",
	'nowdoc' => "<?php\n\$a = <<<'EOT'\nclass NotAClass {}\nEOT;\nclass Real {}\n",
	'indented heredoc' => "<?php\n\$a = <<<EOT\n    class NotAClass {}\n    EOT;\nclass Real {}\n",
	'backtick' => "<?php\n\$a = `echo class Nope`;\nclass Real {}\n",
	'string with keyword' => "<?php\n\$a = 'class NotAClass';\nclass Real {}\n",
	'inline html' => "<?php class A {} ?>\n<p>class NotAClass {}</p>\n<?php class B {}\n",
	'short echo tag' => "<?php class A {} ?>\n<?= 'x' ?>\n<?php class B {}\n",
	'no php' => "plain text class NotAClass\n",
	'property named class' => "<?php\nclass A { public \$class = 1; }\n\$x = Foo::class;\n",
	'high byte names' => "<?php\nclass Caf\xc3\xa9 {}\nconst \xc3\x84 = 1;\n",
	'trait' => "<?php\ntrait T { const X = 1; public function m() {} }\n",
	'nested braces' => "<?php\nclass A { public function f() { if (true) { } } const C = 1; }\nconst G = 1;\n",
	'empty file' => "",
	'only open tag' => "<?php\n",
	// php_strip_whitespace() is a lexer: inside {$...} a nested "..." is a
	// separate string, whitespace collapses to one space (newlines too, which
	// the cleaner's // skip depends on), and after a heredoc's closing label
	// the next token is written verbatim, a comment included, then "\n"
	'hash in interpolation' => "<?php\n\$s = \"{\$a[\"#\"]}\"; class HashInterp {}\nfunction afterHash() {}\n",
	'block comment opener in interpolation' => "<?php\n\$s = \"{\$a[\"/*\"]}\";\nclass CommentInterp {}\n// */\nclass AfterComment {}\n",
	'line comment opener in interpolation' => "<?php\n\$s = \"{\$a[\"//\"]}\";\nclass Foo {}\n",
	'single quotes in interpolation' => "<?php\n\$s = \"{\$a['//']}\"; class SlashInterp {}\n",
	'quote in interpolated single-quoted key' => "<?php\n\$x = \"{\$a['\"']}\"; \$y = '#'; function f1() {}\nfunction f2() {}\n",
	'heredoc interpolation' => "<?php\n\$s = <<<EOT\n{\$a[\"#\"]}\nEOT;\nclass HeredocInterp {}\n",
	'comment after heredoc label' => "<?php\n\$x = <<<EOT\nabc\nEOT# class Foo {}\n;\n",
	'dollar brace interpolation' => "<?php\n\$s = \"\${a[\"#\"]}\"; class DollarBrace {}\n",
	'nested braces in interpolation' => "<?php\n\$s = \"{\$a[f(function () { return \"#\"; })]}\"; class NestedBraces {}\n",
	'backtick interpolation' => "<?php\n\$s = `{\$a[\"#\"]}`; class BacktickInterp {}\n",
	'var offset quote' => "<?php\n\$s = \"\$a[\"]\"; class VarOffset {}\n",
	'property fetch in string' => "<?php\n\$s = \"\$a->b # \"; class PropertyFetch {}\n",
	'yield from with comment' => "<?php\nfunction g() { yield /* \" */ from x(); }\nclass AfterYield {}\n\$s = \"\";\n",
	'cast with spaces' => "<?php\n\$a = (  int  ) \$b; class AfterCast {}\n",
	'windows newlines' => "<?php\r\n\$a = 1; // x\r\nclass Crlf {}\r\n",
	'shebang' => "#!/usr/bin/env php\n<?php\nclass Shebang {}\n",
	'open tag at eof' => "<?php",
	'unterminated comment' => "<?php\nclass Before {}\n/* class After {}\n",
	// fuzz-derived
	'fuzz: heredoc comment between interpolations' => "<?php\nconst C400 = 1; \$x = \"{\$a[\"#\"]}\"; \$x = <<<EOT\nabc\nEOT# c\n;\n\$x = \"{\$a[\"#\"]}\"; function f404() {} \$x = \"\${a}\"; ",
	'fuzz: interpolations and a quoted key' => "<?php\n\$x = \"{\$a[\"//\"]}\";\nfunction f521() {} \$x = <<<EOT\nabc\nEOT# c\n;\nfunction f523() {} \$x = \"{\$a['\"']}\"; \$x = '/*'; ",
	'fuzz: var offset, yield from and a heredoc' => "<?php\n\$x = \"\$a[#]\"; yield /* \" */ from x();\n\$x = \"a#b\"; \$x = <<<EOT\n{\$a[\"#\"]} #\nEOT;\n\$x = \"{\$a[\"//\"]}\";\nconst C2135 = 1; ",
];

$dir = sys_get_temp_dir() . '/phpstan-symbol-finder-' . getmypid();
@mkdir($dir);
$fixtureFiles = [];
$i = 0;
foreach ($fixtures as $label => $source) {
	$path = sprintf('%s/fixture-%02d.php', $dir, $i++);
	file_put_contents($path, $source);
	$fixtureFiles[$path] = $label;
}
foreach ($fixtureFiles as $path => $label) {
	$compare([$path], $label . ': ');
}
printf("fixtures: %d checks\n", $checked);
foreach (array_keys($fixtureFiles) as $path) {
	@unlink($path);
}
@rmdir($dir);

// ---- how the files are reached ----
// The twin reads each file through php_strip_whitespace(), i.e. PHP's stream
// layer: stream-wrapper paths (file://, user wrappers, phar://) are found,
// and the result is keyed like `$result[$file] = ...` (a numeric-string path
// becomes an int key). Anything but a string throws the twin's TypeError, a
// path with a NUL byte php_strip_whitespace()'s ValueError. The twin is the
// real class here, so the messages compare modulo the prefix.
$twin = new PHPStan\Reflection\BetterReflection\SourceLocator\SymbolFinderInFiles(new PHPStan\Reflection\BetterReflection\SourceLocator\PhpFileCleaner());
$observe = static function (object $finder, array $files): array {
	try {
		return ['result', $finder->findSymbols($files, true)];
	} catch (\Throwable $e) {
		return [get_class($e), str_replace('PHPStanTurbo\\SymbolFinderInFiles', 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\SymbolFinderInFiles', preg_replace('~, called in .*$~', '', $e->getMessage()))];
	}
};
final class SymbolFinderCorpusStreamWrapper
{

	/** @var resource|null */
	public $context;

	private string $data = '';

	private int $position = 0;

	public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
	{
		if (str_contains($path, 'throw')) {
			throw new \RuntimeException('the wrapper refuses ' . $path);
		}
		$this->data = "<?php\nnamespace Wrapped;\nclass ViaWrapper {}\nfunction viaWrapper() {}\n";
		return true;
	}

	public function stream_read(int $count): string
	{
		$chunk = substr($this->data, $this->position, $count);
		$this->position += strlen($chunk);
		return $chunk;
	}

	public function stream_eof(): bool
	{
		return $this->position >= strlen($this->data);
	}

	public function stream_stat(): array
	{
		return ['size' => strlen($this->data), 'mode' => 0100644];
	}

	public function stream_set_option(int $option, int $arg1, ?int $arg2): bool
	{
		return false;
	}

}
stream_wrapper_register('symbolfindercorpus', SymbolFinderCorpusStreamWrapper::class);
$streamDir = sys_get_temp_dir() . '/phpstan-symbol-finder-streams-' . getmypid();
@mkdir($streamDir);
file_put_contents($streamDir . '/plain.php', "<?php\nclass Plain {}\n");
file_put_contents($streamDir . '/123', "<?php\nclass Numeric {}\n");
// a phar:// path, the case ResultCacheManager scans for (a phar can only be
// written with phar.readonly=0, which is INI_SYSTEM: a child process builds it)
$pharFile = $streamDir . '/lib.phar';
if (extension_loaded('phar')) {
	exec(escapeshellarg(PHP_BINARY) . ' -d phar.readonly=0 -r ' . escapeshellarg(sprintf(
		'$p = new Phar(%s); $p->addFromString("src/Foo.php", "<?php\\nnamespace Lib;\\nclass Foo {}\\nfunction bar() {}\\n"); $p->setStub("<?php __HALT_COMPILER();");',
		var_export($pharFile, true),
	)));
}
$previousCwd = getcwd();
chdir($streamDir);
$streamCases = [
	'file:// path' => ['file://' . $streamDir . '/plain.php'],
	'user stream wrapper' => ['symbolfindercorpus://anything.php'],
	'phar:// path' => ['phar://' . $pharFile . '/src/Foo.php'],
	'throwing stream wrapper' => ['symbolfindercorpus://throw.php', $streamDir . '/plain.php'],
	'numeric-string path' => ['123'],
	'relative path' => ['plain.php'],
	'missing file' => [$streamDir . '/missing.php'],
	'directory' => [$streamDir],
	'int entry' => [$streamDir . '/plain.php', 42],
	'null entry' => [null],
	'NUL byte in the path' => [$streamDir . "/plain.php\0.txt"],
];
$streamResults = [];
foreach ($streamCases as $label => $files) {
	$a = $observe($native, $files);
	$b = $observe($twin, $files);
	$streamResults[$label] = $b;
	$checked++;
	if ($a !== $b) {
		$failures++;
		printf("FAIL: %s\n  native: %s\n  php   : %s\n", $label, json_encode($a), json_encode($b));
	}
}
chdir($previousCwd);
@unlink($streamDir . '/plain.php');
@unlink($streamDir . '/123');
@unlink($pharFile);
@rmdir($streamDir);
// the cases above must not pass vacuously
$expected = ['result', ['symbolfindercorpus://x.php' => [['wrapped\\viawrapper'], ['wrapped\\viawrapper'], []]]];
if ($observe($twin, ['symbolfindercorpus://x.php']) !== $expected) {
	$failures++;
	printf("FAIL: the twin reads through the stream wrapper: %s\n", json_encode($observe($twin, ['symbolfindercorpus://x.php'])));
}
if (extension_loaded('phar') && ($streamResults['phar:// path'] ?? null) !== ['result', ['phar://' . $pharFile . '/src/Foo.php' => [['lib\\foo'], ['lib\\bar'], []]]]) {
	$failures++;
	printf("FAIL: the phar fixture is scanned: %s\n", json_encode($streamResults['phar:// path'] ?? null));
}

// ---- repo corpus ----
$corpusStart = $checked;
$files = [];
foreach (['src', 'tests', 'build', 'compiler', 'e2e', 'turbo-ext', 'vendor'] as $sub) {
	if (!is_dir($root . '/' . $sub)) {
		continue;
	}
	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $sub, FilesystemIterator::SKIP_DOTS));
	foreach ($iterator as $file) {
		if ($file->isFile() && in_array($file->getExtension(), ['php', 'inc', 'stub'], true)) {
			$files[] = $file->getPathname();
		}
	}
}
sort($files);

// batched, so the native side exercises its reusable buffers
foreach (array_chunk($files, 400) as $chunk) {
	$compare($chunk, '');
}
printf("corpus: %d files, %d checks\n", count($files), $checked - $corpusStart);

echo $failures === 0 ? "ALL OK\n" : "$failures FAILURES\n";
exit($failures === 0 ? 0 : 1);
