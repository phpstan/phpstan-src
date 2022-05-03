<?php declare(strict_types = 1);

namespace Bug7166;

use function PHPStan\dumpType;

class HelloWorld
{
	public static function print(
		string $value
	): void {
		$isSingleLine = strpos($value, "\n") === false;
		dumpType($value);
		$hasLeadingSpace = $value !== '' && ($value[0] === ' ' || $value[0] === '\t');
	}
}
