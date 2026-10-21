<?php declare(strict_types = 1);

// Loaded through bootstrapFiles, so it runs in the main process and in every
// worker - forked ones included, which is exactly where the per-worker `-d`
// mitigations could not reach. One JSON line per process.

// XDEBUG_MODE wins over xdebug.mode, so the effective mode is what says whether
// xdebug is armed
$xdebugMode = getenv('XDEBUG_MODE');
if ($xdebugMode === false || $xdebugMode === '') {
	$xdebugMode = (string) ini_get('xdebug.mode');
}

file_put_contents(
	__DIR__ . '/probe.log',
	json_encode([
		'pid' => getmypid(),
		'xdebug_loaded' => extension_loaded('xdebug'),
		'xdebug_mode' => $xdebugMode,
		'pcov_loaded' => extension_loaded('pcov'),
		'pcov_enabled' => (string) ini_get('pcov.enabled'),
		'uopz_loaded' => extension_loaded('uopz'),
		'uopz_disable' => (string) ini_get('uopz.disable'),
		'opcache_cli' => (string) ini_get('opcache.enable_cli'),
		'restarted_for' => (string) getenv('PHPSTAN_RESTART'),
		'restart_ini_pointer' => (string) getenv('PHPSTAN_RESTART_INI'),
		'phprc' => (string) getenv('PHPRC'),
		'loaded_ini' => (string) php_ini_loaded_file(),
		'memory_limit' => (string) ini_get('memory_limit'),
	], JSON_THROW_ON_ERROR) . "\n",
	FILE_APPEND,
);
