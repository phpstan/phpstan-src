<?php declare(strict_types = 1);

// A hand-written autoloader, not Composer's: it includes the file for the requested
// name and does not check what the file declared.
spl_autoload_register(static function (string $class): void {
	$prefix = 'BootstrapAutoloaderWrongFile\\';
	if (!str_starts_with($class, $prefix)) {
		return;
	}

	$file = __DIR__ . '/src/' . substr($class, strlen($prefix)) . '.php';
	if (!is_file($file)) {
		return;
	}

	require $file;
});
