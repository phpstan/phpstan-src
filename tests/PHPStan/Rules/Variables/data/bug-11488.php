<?php

namespace Bug11488;

class Foo
{
	/**
	 * @param array{mixed}|array{mixed, string|null, mixed} $row
	 */
	protected function test(array $row): string
	{
		if (count($row) !== 1) {
			[$field, $operator, $value] = $row;
			return $operator ?? '=';
		}

		return '';
	}
}
