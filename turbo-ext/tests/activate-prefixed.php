<?php declare(strict_types = 1);

/**
 * Bootstrap of the differential tests: declares the native classes as
 * PHPStanTurbo\* next to the PHP twins in this process (the enabler is
 * deliberately NOT run — with it, the twins' names would be the native
 * classes and there would be nothing to compare against).
 *
 * Returns the manifest of shadowed pairs (vendor/turbo-shadowed-classes.json)
 * and the class map (vendor/turbo-class-map.php).
 *
 * @return array{manifest: array<string, array{php: string, cpp: string, turboClass: string, final: bool, parent: string|null, interfaces: list<class-string>, vendored?: bool}>, classMap: array<string, string>}
 */

$root = dirname(__DIR__, 2);

if (!extension_loaded('phpstan_turbo')) {
	fwrite(STDERR, "the phpstan_turbo extension is not loaded\n");
	exit(2);
}

require_once $root . '/vendor/autoload.php';

$manifestFile = $root . '/vendor/turbo-shadowed-classes.json';
$classMapFile = $root . '/vendor/turbo-class-map.php';
if (!is_file($manifestFile) || !is_file($classMapFile)) {
	fwrite(STDERR, "vendor/turbo-shadowed-classes.json or vendor/turbo-class-map.php does not exist — run composer dump-autoload first\n");
	exit(2);
}
$manifest = json_decode(file_get_contents($manifestFile), true, 8, JSON_THROW_ON_ERROR);
$classMap = require $classMapFile;

// the referenced classes are the real PHP classes here
\PHPStanTurbo\Runtime::configure($classMap);

if (!\PHPStanTurbo\Runtime::isShadowing()) {
	\PHPStanTurbo\Runtime::activateShadowing([], 'PHPStanTurbo\\');
}

return ['manifest' => $manifest, 'classMap' => $classMap];
