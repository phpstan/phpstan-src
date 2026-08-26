<?php declare(strict_types = 1);

// The shape Illuminate\Foundation\AliasLoader creates: a prepended autoloader that
// resolves a short alias to a real class with class_alias(), reading no file. The alias
// names collide with global functions - Laravel's Cache, File, Str, Hash all do.
require_once __DIR__ . '/src/Real.php';

spl_autoload_register(static function (string $class): void {
	if ($class !== 'File') {
		return;
	}

	class_alias(E2eFacadeAlias\Real::class, 'File');
}, true, true);
