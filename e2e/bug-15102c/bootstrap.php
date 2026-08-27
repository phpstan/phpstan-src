<?php declare(strict_types = 1);

// The shape Illuminate\Foundation\AliasLoader creates in a real Laravel app: a prepended
// autoloader resolving a short alias with class_alias(), where the aliased class is not
// loaded yet - Composer autoloads it on demand when class_alias() asks for it. The alias
// name collides with a global helper function ('Redirect' vs redirect()), like Laravel's
// Redirect, Cache, View and Session aliases do.
require_once __DIR__ . '/vendor/autoload.php';

spl_autoload_register(static function (string $class): void {
	if ($class !== 'Redirect') {
		return;
	}

	class_alias(E2eFacadeAlias\Redirect::class, 'Redirect');
}, true, true);
