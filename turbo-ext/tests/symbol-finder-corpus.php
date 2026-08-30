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
require $root . '/vendor/autoload.php';

if (!extension_loaded('phpstan_turbo')) {
	fwrite(STDERR, "extension not loaded\n");
	exit(2);
}

// Declares the stub subclasses before the autoloader can load the twins.
PHPStan\Turbo\TurboExtensionEnabler::enableIfLoaded();

use PHPStan\Reflection\BetterReflection\SourceLocator\PhpFileCleaner;
use PHPStan\Reflection\BetterReflection\SourceLocator\SymbolFinderInFiles;

$native = new SymbolFinderInFiles(new PhpFileCleaner());
if (get_parent_class($native) !== 'PHPStanTurbo\SymbolFinderInFiles') {
	fwrite(STDERR, "the native class is not shadowing the twin — is the extension version current?\n");
	exit(2);
}

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
