<?php

namespace Bug4587;

use function PHPStan\Analyser\assertType;

class HelloWorld
{
	public function a(): void
	{
		/** @var list<array{a: int}> $results */
		$results = [];

		$type = array_map(static function (array $result): array {
			assertType('array(\'a\' => int)', $result);
			return $result;
		}, $results);

		assertType('array<int, array(\'a\' => int)>', $type);
	}

	public function b(): void
	{
		/** @var list<array{a: int}> $results */
		$results = [];

		$type = array_map(static function (array $result): array {
			assertType('array(\'a\' => int)', $result);
			$result['a'] = (string) $result['a'];
			assertType('array(\'a\' => string&numeric)', $result);

			return $result;
		}, $results);

		assertType('array<int, array(\'a\' => string&numeric)>', $type);
	}
}
