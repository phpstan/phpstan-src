<?php declare(strict_types=1);

// Differential test: native PHPStanTurbo\PhpFileCleaner vs the PHP twin.
// The cleaned text feeds the regex that becomes the directory symbol index,
// so the bar is byte-identical output — over every PHP file in the repo
// (vendor included) plus synthetic fixtures for constructs the repo corpus
// does not necessarily contain.
//
// Run: php -d extension=.../phpstan_turbo.so turbo-ext/tests/php-file-cleaner-corpus.php

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';

if (!extension_loaded('phpstan_turbo')) {
	fwrite(STDERR, "extension not loaded\n");
	exit(2);
}

// Declares the stub subclasses before the autoloader can load the twins.
PHPStan\Turbo\TurboExtensionEnabler::enableIfLoaded();

use PHPStan\Reflection\BetterReflection\SourceLocator\PhpFileCleaner;

$native = new PhpFileCleaner();
if (get_parent_class($native) !== 'PHPStanTurbo\PhpFileCleaner') {
	fwrite(STDERR, "the native class is not shadowing the twin — is the extension version current?\n");
	exit(2);
}

// The reference is the twin's own source with the class renamed, so it cannot
// drift from the file the port mirrors.
$twinFile = $root . '/src/Reflection/BetterReflection/SourceLocator/PhpFileCleaner.php';
$twin = file_get_contents($twinFile);
eval(str_replace(
	'final class PhpFileCleaner',
	'final class ReferencePhpFileCleaner',
	substr($twin, strpos($twin, 'final class PhpFileCleaner')),
));
$reference = new ReferencePhpFileCleaner();

$failures = 0;
$checked = 0;

/**
 * The twin's clean() is only ever fed php_strip_whitespace() output, so that
 * is what both sides get here too; both maxMatches regimes are exercised
 * because maxMatches === 1 arms the early return.
 */
$compare = static function (string $label, string $contents) use ($native, $reference, &$failures, &$checked): void {
	$m = [];
	if (!preg_match_all('{\b(?:(?:class|interface|trait|const|function|enum)\s)|(?:define\s*\()}i', $contents, $m)) {
		return;
	}

	foreach ([count($m[0]), 1] as $maxMatches) {
		$checked++;
		$a = $native->clean($contents, $maxMatches);
		$b = $reference->clean($contents, $maxMatches);
		if ($a === $b) {
			continue;
		}

		$failures++;
		if ($failures > 5) {
			continue;
		}

		$at = 0;
		$min = min(strlen($a), strlen($b));
		while ($at < $min && $a[$at] === $b[$at]) {
			$at++;
		}
		printf("FAIL: %s (maxMatches=%d) differs at byte %d (lengths %d/%d)\n", $label, $maxMatches, $at, strlen($a), strlen($b));
		printf("  native: %s\n", var_export(substr($a, max(0, $at - 40), 90), true));
		printf("  php   : %s\n", var_export(substr($b, max(0, $at - 40), 90), true));
	}
};

// ---- synthetic fixtures ----
// Constructs whose handling differs between the twin's regexes and the
// hand-rolled native matchers, or which the repo corpus may not contain.
$fixtures = [
	'heredoc' => "<?php\n\$a = <<<EOT\nclass NotAClass {}\nEOT;\nclass Real {}\n",
	'indented heredoc' => "<?php\n\$a = <<<EOT\n    class NotAClass {}\n    EOT;\nclass Real {}\n",
	'nowdoc' => "<?php\n\$a = <<<'EOT'\nclass NotAClass {}\nEOT;\n",
	'heredoc label prefix' => "<?php\n\$a = <<<EOT\nEOTX\nEOT;\nconst A = 1;\n",
	'heredoc with quotes' => "<?php\n\$a = <<<\"EOT\"\nclass X {}\nEOT;\n",
	'hash comment' => "<?php\n# class Commented {}\nclass Real {}\n",
	'attribute' => "<?php\n#[Attr(class: 'x')]\nclass Real {}\n",
	'line comment' => "<?php\n// class Commented {}\nclass Real {}\n",
	'block comment' => "<?php\n/* class Commented {} */\nclass Real {}\n",
	'unterminated block comment' => "<?php\n/* class Commented {}\n",
	'uppercase keywords' => "<?php\nCLASS Upper {}\nCONST X = 1;\n",
	'class constant' => "<?php\nclass A { const FOO = 1; public const BAR = 2; }\nconst GLOBAL_ONE = 3;\n",
	'nested braces in class' => "<?php\nclass A { public function f() { if (true) { } } const FOO = 1; }\nconst G = 1;\n",
	'define with double quotes' => "<?php\ndefine(\"FOO\\\\BAR\", 1);\n",
	'define with variable name' => "<?php\ndefine(\$name, 1);\n\$x = 'function evil(';\n",
	'define spacing' => "<?php\ndefine   (   'SPACED', 1);\n",
	'escaped quotes' => "<?php\n\$a = 'it\\'s class X'; class Real {}\n",
	'string with backslash' => "<?php\n\$a = 'ends with backslash\\\\'; class Real {}\n",
	'close tag and inline html' => "<?php\nclass A {}\n?>\n<p>class NotAClass {}</p>\n<?php\nclass B {}\n",
	'short echo tag' => "<?php\nclass A {}\n?>\n<?= 'class NotAClass' ?>\n<?php class B {}\n",
	'anon class' => "<?php\n\$a = new class extends Foo implements Bar {};\nclass Real {}\n",
	'property named class' => "<?php\nclass A { public \$class = 1; }\n\$x = Foo::class;\n\$y = \$obj->class;\n",
	'function by reference' => "<?php\nfunction &refFn() {}\nfunction plain(): void {}\n",
	'namespace braces' => "<?php\nnamespace A\\B { class C {} }\nnamespace D { const E = 1; }\n",
	'no php tag' => "just text, no php at all\n",
	'php tag at eof' => "<?php class A {} <?",
	'high byte identifiers' => "<?php\nclass Caf\xc3\xa9 {}\nconst \xc3\x84 = 1;\n",
	'trait and enum' => "<?php\ntrait T { const X = 1; }\nenum E: string { case A = 'a'; const Y = 2; }\n",
	'interface const' => "<?php\ninterface I { const X = 1; }\n",
];
foreach ($fixtures as $label => $source) {
	$tmp = tempnam(sys_get_temp_dir(), 'pfc');
	file_put_contents($tmp, $source);
	$stripped = @php_strip_whitespace($tmp);
	unlink($tmp);
	if ($stripped === '' || $stripped === false) {
		continue;
	}
	$compare('fixture ' . $label, $stripped);
}
printf("fixtures: %d (file, maxMatches) pairs checked\n", $checked);

// ---- repo corpus ----
$corpusStart = $checked;
$files = [];
foreach (['src', 'tests', 'build', 'compiler', 'e2e', 'turbo-ext', 'vendor'] as $dir) {
	if (!is_dir($root . '/' . $dir)) {
		continue;
	}
	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $dir, FilesystemIterator::SKIP_DOTS));
	foreach ($iterator as $file) {
		if ($file->isFile() && in_array($file->getExtension(), ['php', 'inc', 'stub'], true)) {
			$files[] = $file->getPathname();
		}
	}
}
sort($files);

foreach ($files as $file) {
	$contents = @php_strip_whitespace($file);
	if ($contents === '' || $contents === false) {
		continue;
	}
	$compare($file, $contents);
}
printf("corpus: %d files, %d (file, maxMatches) pairs checked\n", count($files), $checked - $corpusStart);

echo $failures === 0 ? "ALL OK\n" : "$failures FAILURES\n";
exit($failures === 0 ? 0 : 1);
