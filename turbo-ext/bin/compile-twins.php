<?php declare(strict_types = 1);

/**
 * Compiles the PHP twins listed in turbo-ext/compiled-classes.php to their
 * native implementations (PHPStan\Build\TurboCodegen): the .cpp file each
 * twin's ShadowedByTurboExtension attribute names, plus
 * turbo-ext/src/CodegenDecls.h with the compiled methods' C++ entry points.
 *
 * Usage: php turbo-ext/bin/compile-twins.php [--check]
 *   --check  fail when a generated file differs from what is on disk
 */

use PHPStan\Build\TurboCodegen\Compiler;
use PHPStan\Turbo\ShadowedByTurboExtension;

error_reporting(E_ALL);
ini_set('memory_limit', '4G');

$root = dirname(__DIR__, 2);
chdir($root);

require 'vendor/autoload.php';
foreach (glob('build/PHPStan/Build/TurboCodegen/*.php') as $file) {
	require_once $file;
}

$classes = [];
foreach (require 'turbo-ext/compiled-classes.php' as $className => $config) {
	if (is_string($config)) {
		$config = ['ce' => $config];
	}
	$reflection = new ReflectionClass($className);
	$attributes = $reflection->getAttributes(ShadowedByTurboExtension::class);
	if (count($attributes) !== 1) {
		fwrite(STDERR, "$className is not shadowed\n");
		exit(1);
	}
	$implementation = $attributes[0]->newInstance()->implementation;
	$classes[$className] = [
		'file' => substr((string) $reflection->getFileName(), strlen($root) + 1),
		'stem' => basename($implementation, '.cpp'),
		'implementation' => 'turbo-ext/src/' . basename($implementation),
	] + $config;
}

$check = in_array('--check', $argv, true);
$stale = 0;
foreach ((new Compiler($classes))->compile() as $path => $content) {
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
