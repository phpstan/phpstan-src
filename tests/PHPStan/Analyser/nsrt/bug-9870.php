<?php declare(strict_types = 1);

namespace Bug9870;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param non-empty-string|list<non-empty-string> $date
	 */
	public function sayHello($date): void
	{
		if (is_string($date)) {
			assertType('non-empty-string', str_replace('-', '/', $date));
		} else {
			assertType('list<non-empty-string>', str_replace('-', '/', $date));
		}
		assertType('list<non-empty-string>|non-empty-string', str_replace('-', '/', $date));
	}

	/**
	 * @param string|array<string> $stringOrArray
	 * @param non-empty-string|array<string> $nonEmptyStringOrArray
	 * @param string|array<non-empty-string> $stringOrArrayNonEmptyString
	 * @param string|non-empty-array<string> $stringOrNonEmptyArray
	 * @param string|array<string>|bool|int $wrongParam
	 */
	public function moreCheck(
		$stringOrArray,
		$nonEmptyStringOrArray,
		$stringOrArrayNonEmptyString,
		$stringOrNonEmptyArray,
		$wrongParam,
	): void {
		assertType('array<string>|string', str_replace('-', '/', $stringOrArray));
		assertType('array<string>|non-empty-string', str_replace('-', '/', $nonEmptyStringOrArray));
		assertType('array<non-empty-string>|string', str_replace('-', '/', $stringOrArrayNonEmptyString));
		assertType('non-empty-array<string>|string', str_replace('-', '/', $stringOrNonEmptyArray));
		assertType('array<string>|string', str_replace('-', '/', $wrongParam));
		assertType('array<string>|string', str_replace('-', '/'));
	}
}
