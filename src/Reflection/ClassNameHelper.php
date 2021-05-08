<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use Nette\Utils\Strings;

class ClassNameHelper
{

	public static function isValidClassName(string $name): bool
	{
		return Strings::match($name, '/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/') !== null;
	}

}
