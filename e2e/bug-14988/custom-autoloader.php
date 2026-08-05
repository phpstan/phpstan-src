<?php declare(strict_types=1);

namespace Redeclare;

use function is_file;
use function spl_autoload_register;
use function strlen;
use function strncmp;
use function substr;

/**
 * Stands in for the real-world autoloader that reproduces phpstan/phpstan#14988: PHP_CodeSniffer's
 * own autoloader, loaded via bootstrapFiles for a package that ships no Composer autoload metadata.
 * It shares the three properties that make it fatal:
 *
 *  1. It is registered as a string callable ("Class::method"), which spl_autoload_functions()
 *     normalises to ['Class', 'method'] - an array whose first element is a string, not a
 *     ClassLoader object - so it survives bin/phpstan's Composer ClassLoader exclusion.
 *  2. It is catch-all: it resolves names outside its own namespace.
 *  3. It uses a plain include (not include_once), so a file already loaded is executed again.
 */
final class Autoloader
{

	public static function load(string $class): void
	{
		if (strncmp($class, 'Redeclare\\Builder\\', 18) !== 0) {
			return;
		}

		$name = substr($class, strlen('Redeclare\\Builder\\'));
		foreach (['/classes/', '/pkg/'] as $dir) {
			$file = __DIR__ . $dir . $name . '.php';
			if (is_file($file)) {
				include $file;
				return;
			}
		}
	}

}

spl_autoload_register('Redeclare\\Autoloader::load', true, true);
