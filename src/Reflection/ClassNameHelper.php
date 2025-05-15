<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use Nette\Utils\Strings;
use function array_key_exists;
use function ltrim;

final class ClassNameHelper
{

	/** @var array<string, bool> */
	private static array $checked = [];

	public static function isValidClassName(string $name): bool
	{
		if (!array_key_exists($name, self::$checked)) {
			// from https://stackoverflow.com/questions/3195614/validate-class-method-names-with-regex#comment104531582_12011255
			self::$checked[$name] = Strings::match(ltrim($name, '\\'), '/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*(\\\\[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)*$/') !== null;

		}
		return self::$checked[$name];
	}

}
