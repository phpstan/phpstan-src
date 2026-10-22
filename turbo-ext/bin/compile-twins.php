<?php declare(strict_types = 1);

/**
 * Compiles the classes marked with #[CompiledIntoTurboExtension] (and the
 * vendored ones build/PHPStan/Build/TurboAttributeCollector.php hardcodes)
 * to their native implementations (PHPStan\Build\TurboCodegen): the .cpp
 * file the collector derives from each class name, plus
 * turbo-ext/src/CodegenDecls.h with the compiled methods' C++ entry points.
 *
 * Usage: php turbo-ext/bin/compile-twins.php [--check]
 *   --check  fail when a generated file differs from what is on disk
 */

use PHPStan\Build\TurboAttributeCollector;
use PHPStan\Build\TurboCodegen\CompileError;
use PHPStan\Build\TurboCodegen\Compiler;

error_reporting(E_ALL);
ini_set('memory_limit', '4G');

$root = dirname(__DIR__, 2);
chdir($root);

require 'vendor/autoload.php';
require_once 'build/PHPStan/Build/TurboAttributeCollector.php';
foreach (glob('build/PHPStan/Build/TurboCodegen/*.php') as $file) {
	require_once $file;
}

$collected = (new TurboAttributeCollector($root))->collect();
$classes = [];
foreach ($collected['compiled'] as $className => $settings) {
	$classes[$className] = [
		'file' => $collected['manifest'][$className]['php'],
		'implementation' => $collected['manifest'][$className]['cpp'],
	] + $settings;
}

$check = in_array('--check', $argv, true);
$stale = 0;
try {
	$compiled = (new Compiler($classes, array_keys($collected['manifest'])))->compile();
} catch (CompileError $e) {
	fwrite(STDERR, $e->getMessage() . "\n");
	exit(1);
}
foreach ($compiled as $path => $content) {
	if (is_file($path) && file_get_contents($path) === $content) {
		continue;
	}
	if ($check) {
		fwrite(STDERR, "stale: $path\n");
		$stale++;
		continue;
	}
	file_put_contents($path, $content);
	echo "wrote $path\n";
}
exit($stale > 0 ? 1 : 0);
