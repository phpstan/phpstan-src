<?php declare(strict_types = 1);

namespace Bug11488;

use function PHPStan\Testing\assertType;

class Foo
{
	/**
	 * @param array{mixed}|array{mixed, string|null, mixed} $row
	 */
	protected function test(array $row): string
	{
		if (count($row) !== 1) {
			assertType('array{mixed, string|null, mixed}', $row);

			[$field, $operator, $value] = $row;
			assertType('string|null', $operator);
			return $operator ?? '=';
		}

		return '';
	}
}
