<?php

require_once __DIR__ . '/vendor/autoload.php';

// Registered *after* Composer's class loader: the third argument (prepend)
// defaults to false, so this ends up at the back of the spl_autoload queue.
//
// \other12972\MyClass is part of Composer's class map (see composer.json), so at
// runtime Composer's class loader resolves it first and this autoloader is never
// invoked for it - running `php real-world.php` therefore never throws.
//
// PHPStan must mirror that order: the Composer class map source locator has to
// resolve the class before this bootstrap autoloader is consulted. Analysing the
// project used to invoke this autoloader first, hitting the LogicException and
// crashing with an internal error that cannot happen at runtime.
spl_autoload_register(function($class) {
	if ($class === \other12972\MyClass::class) {
		throw new LogicException('this should not happen');
	}
});
