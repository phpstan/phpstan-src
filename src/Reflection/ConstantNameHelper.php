<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use function array_filter;
use function array_slice;
use function end;
use function explode;
use function implode;
use function str_contains;
use function strtolower;

class ConstantNameHelper
{

	public static function normalize(string $name): string
	{
		if (!str_contains($name, '\\')) {
			return $name;
		}

		$nameParts = array_filter(explode('\\', $name), static fn ($part) => $part !== '');
		return strtolower(implode('\\', array_slice($nameParts, 0, -1))) . '\\' . end($nameParts);
	}

}
