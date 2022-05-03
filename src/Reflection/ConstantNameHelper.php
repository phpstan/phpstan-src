<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use function array_slice;
use function end;
use function explode;
use function implode;
use function strpos;
use function strtolower;

class ConstantNameHelper
{

	public static function normalize(string $name): string
	{
		if (strpos($name, '\\') === false) {
			return $name;
		}

		$nameParts = explode('\\', $name);
		return strtolower(implode('\\', array_slice($nameParts, 0, -1))) . '\\' . end($nameParts);
	}

}
