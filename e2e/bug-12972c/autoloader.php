<?php

require_once __DIR__ . '/vendor/autoload.php';

// Registered *before* Composer's class loader (third argument = prepend).
// At runtime this resolves \shared12972c\Thing before Composer ever sees it,
// so PHPStan must consult it before the Composer class map as well.
spl_autoload_register(function ($class) {
	if ($class === \shared12972c\Thing::class) {
		require __DIR__ . '/prepended/Thing.php';
	}
}, true, true);
