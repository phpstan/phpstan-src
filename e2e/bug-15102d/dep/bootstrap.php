<?php declare(strict_types = 1);

// The shape deptrac's bootstrap.php creates: an autoloader that lazily requires a nested
// Composer-style autoloader and memoizes the result in a closure static. The file-read
// trap's pseudo-include makes that require "succeed" with dummy data, so the memo ends up
// holding an int and the require is never retried.
spl_autoload_register(static function (string $class): void {
	static $composerAutoloader;

	if (!str_starts_with($class, 'E2eDepInternal\\')) {
		return;
	}

	if ($composerAutoloader === null) {
		$composerAutoloader = require __DIR__ . '/autoload.php';
	}

	if ($composerAutoloader instanceof E2eNestedClassLoader) {
		$composerAutoloader->loadClass($class);
	}
});
