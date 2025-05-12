<?php

spl_autoload_register(function($class) {
	if ($class === \other12972\MyClass::class) {
		require_once __DIR__ . '/src/OTHER/file.php'; // wrong case sensitivity on purpose
	}
});
