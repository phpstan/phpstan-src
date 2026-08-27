<?php declare(strict_types = 1);

// The shape Illuminate\Foundation\AliasLoader creates: a prepended autoloader that resolves a
// short alias with class_alias(). The alias names collide with global functions - Laravel's
// Cache, File, Str and Hash all do - and the alias *target* is autoloaded on demand, so the
// class_alias() call is what pulls it in. Nothing is re-included and no function is redeclared.
require __DIR__ . '/vendor/autoload.php';

spl_autoload_register(static function (string $class): void {
	if ($class !== 'File') {
		return;
	}

	class_alias(E2eFacadeAlias\Real::class, 'File');
}, true, true);
